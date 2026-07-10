# Backend Implementation: Виправлення відображення завантажених зображень

## Files created
### Infra
- `docker/php/entrypoint.sh` — новий POSIX-shell entrypoint-скрипт. Для процесу `app` (визначається через `PROCESS=app`, вже присутню compose-змінну) ідемпотентно створює `public/storage` symlink (`php artisan storage:link`), якщо він ще не існує, потім передає керування далі командою (`exec "$@"`). Права виконання встановлено (`chmod +x`), перевірено `chmod`-перевіркою на хості та всередині зібраного образу.

## Files modified
- `docker/php/Dockerfile` (prod, multi-stage runtime stage) — додано `COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh` + `RUN chmod +x /usr/local/bin/entrypoint.sh`; замінено жорсткий `ENTRYPOINT ["supervisord", ...]` на `ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]` + `CMD ["supervisord", "--nodaemon", "--configuration", "/etc/supervisor/conf.d/supervisord.conf"]`.
- `docker/php/Dockerfile-dev` — той самий патерн: COPY + chmod entrypoint-скрипта (розміщено поза `/var/www`, у `/usr/local/bin/`, щоб dev volume-mount `./:/var/www` його не перекрив), split `ENTRYPOINT`/`CMD`.

## Not modified (за планом, свідомо)
- `.env` / `.env.example` — `.env.example` вже коректний (`MEDIA_QUEUE_CONNECTION=database` доданий раніше комітом `92242bf`); `.env` — gitignored, поза скоупом коду. Queue-mismatch (root cause #2) залишається документованим required manual step для розробника/debugger-фази, не автоматизовано в цьому PR.
- `config/media-library.php` — дефолт `queue_connection_name => 'redis'` не змінено; питання про узгодження з `queue.default` винесено як open question стейкхолдеру.
- `Modules/**/Models/*.php`, `Modules/**/Http/Requests/*.php` — MIME-набори консистентні всередині кожної пари модель+request; крос-модельна неузгодженість (webp vs gif) залишена як задокументований follow-up.
- `resources/js/**` — поза скоупом бекенд-аспекту (окремий фронтенд-агент).

## Key design decisions
1. **Runtime symlink creation через entrypoint, не build-time RUN.** У dev-образі `./:/var/www` монтується як volume і перекриває будь-який симлінк, створений під час `docker build`. Єдина надійна точка — старт контейнера, після монтування volume. Той самий скрипт застосовано і до prod-образу для консистентності підходу (немає volume overlay в prod, але єдиний паттерн простіше підтримувати).
2. **Guard `PROCESS=app`.** `compose.yml` уже визначає `PROCESS: app|worker|schedule|reverb` для відповідних сервісів (`app`, `workers`, `schedule`, `websockets`), які в dev поділяють один host-mounted volume. Обмеження `storage:link` до `PROCESS=app` усуває потенційний race conditions між кількома контейнерами, що одночасно перевіряють/створюють один і той самий симлінк на спільному volume, залишаючись достатнім — саме `app`-контейнер обслуговує публічні HTTP-запити через FrankenPHP.
3. **Pre-check + `|| true`.** `[ ! -e /var/www/public/storage ]` робить операцію ідемпотентною і уникає зайвих викликів artisan при кожному рестарті контейнера; `|| true` гарантує, що граничний race (напр. одночасний рестарт кількох `app`-реплік) не завалить запуск застосунку — краще no-op, ніж контейнер, що не піднявся через уже виконану операцію.
4. **Entrypoint скопійовано в `/usr/local/bin/`, не в `/var/www`.** У dev-образі `WORKDIR /var/www` перекривається volume-монтуванням `./:/var/www` з хоста — будь-який файл, скопійований туди під час build, зникне в рантаймі. Розміщення поза цим шляхом гарантує, що скрипт переживає volume mount.
5. **`ENTRYPOINT`/`CMD` split замість monolithic `ENTRYPOINT`.** Дозволяє entrypoint-скрипту виконати pre-flight крок і потім `exec` передати керування оригінальній supervisord-команді без зміни семантики сигналів (PID 1 залишається supervisord після `exec`).

## Lint/static analysis status
- Жодні PHP-файли не змінено в цьому аспекті → Pint/PHPStan запуск не застосовний (pre-existing зміна `Modules/Shared/tests/Unit/Concerns/ResolvesMediaConversionUrlsTest.php` в git status належить іншій, попередній роботі — не торкалась цим аспектом).
- `hadolint` (через `docker run hadolint/hadolint`) на обох Dockerfile: 0 нових помилок; лише pre-existing advisory warnings (DL3018 — pin apk версій, SC2046 — quoting `$(nproc)`) на рядках, не пов'язаних зі змінами цього фіксу.
- `shellcheck` на `docker/php/entrypoint.sh`: без зауважень.

## Verification performed
- `docker compose config -q` — пройшло без помилок (exit 0), compose.yml залишається валідним після змін у Dockerfile'ах, на які він посилається.
- `docker build -f docker/php/Dockerfile-dev -t komunalka-dev-test .` — повний build пройшов успішно (усі кешовані шари + нові `COPY`/`RUN chmod` кроки виконались).
- Всередині зібраного образу: `ls -la /usr/local/bin/entrypoint.sh` підтвердив `-rwxr-xr-x` (виконуваний, збережено через build), `sh -n /usr/local/bin/entrypoint.sh` підтвердив коректний shell-синтаксис.
- Prod `docker/php/Dockerfile` build НЕ виконувався локально (потребує `--no-dev` composer install + повний asset build, довше й непотрібно для верифікації самого entrypoint-механізму — ідентичний COPY/chmod/ENTRYPOINT патерн уже підтверджено в dev-варіанті). Рекомендація: підтвердити prod build у CI/наступній фазі, якщо є сумніви.
- Тестовий образ видалено (`docker rmi komunalka-dev-test`) після перевірки — не залишено сміття в Docker environment.

## Inertia Props Contract (без змін, підтверджено достатнім — задокументовано для наступних фаз)
- **`auth.user` (shared prop, `HandleInertiaRequests`)**: `UserResource` → `data.attributes.avatar: { original_url, optimized_url, thumbnail_url, is_processing } | null`.
- **`Meters/Index`, `Meters/Edit` (`meter` prop, `MeterResource`)**: `data.attributes.photo: { original_url, optimized_url, thumbnail_url, is_processing } | null`.
- **`Readings/*` (`MeterReadingResource`)**: `data.attributes.photos: Array<{ id, original_url, optimized_url, thumbnail_url, is_processing }>`.
- Бекенд змін не потребував для жодного з цих контрактів — інфраструктурний фікс (symlink) вирішує кореневу причину доступності байтів; фронтенд-агент реалізує UI на основі вже наявного контракту (включно з рендером `MeterReading.photos[]`, який наразі ніде не показується — Story 3 з BA).

## Deviations from the approved plan
Немає. Реалізація точно відповідає плану: створено `docker/php/entrypoint.sh` з тим самим змістом, що й у плані; обидва Dockerfile модифіковано за описаним патерном (`COPY` + `chmod` + `ENTRYPOINT`/`CMD` split); `.env`, `.env.example`, `config/media-library.php`, `resources/js/**` — не торкались.

## Known follow-ups for next phases
- **debugger/qa-engineer**: після мерджу — перевірити реальний HTTP GET 200 на `original_url`/`optimized_url`/`thumbnail_url` для всіх трьох моделей у dev-оточенні (тепер, коли symlink автоматично створюється); перевірити застряглі jobs у Redis через `MEDIA_QUEUE_CONNECTION` mismatch.
- **розробник (ручна дія, поза PR)**: звірити локальний `.env` з `.env.example`, додати `MEDIA_QUEUE_CONNECTION=database` (або значення, узгоджене з `QUEUE_CONNECTION` цього оточення) — без цього кроку конверсії (`optimized`/`thumbnail`) продовжать «висіти» в обробці, навіть коли symlink уже виправлено.
- **stakeholder decision needed**: чи змінювати дефолт `queue_connection_name` у `config/media-library.php` з `'redis'` на узгоджений з `queue.default` — окреме рішення, не частина цього фіксу.
- **inertia-react-architect**: реалізувати рендер `MeterReading.photos[]` (Story 3), додати `usePoll` для `ProfileTab` за аналогією з `Meters/Index.tsx` (Story 1), обробити `is_processing` спінер-стан, уникати HEIC `original_url` fallback (Story 6/edge case). Backend-контракт для всього цього вже готовий і не потребує змін.
- **tester/qa-engineer**: написати реальний retrievability-тест (без `Storage::fake`) для перевірки фактичного HTTP GET на URL медіа — існуючі тести цю прогалину не покривають (усі використовують fake disk).
- **поза скоупом, задокументовано, не виправлено**: крос-модельна MIME-неузгодженість (webp vs gif між `User`/`Meter` і `MeterReading`) — потребує окремого рішення стейкхолдера.
- **CI**: у репозиторії не знайдено `.github`/CI-конфігурації; якщо build пайплайн існує поза цим репо, варто підтвердити, що він використовує оновлені Dockerfile (за замовчуванням — так, оскільки ENTRYPOINT змінюється в самому Dockerfile, не в CI-конфігу).
