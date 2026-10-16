export const ASSISTANT_NAME = 'ХаткоБот'

export const ASSISTANT_TITLE = 'ХаткоБот — AI-помічник'

export const ASSISTANT_STATUS_LABEL = 'Онлайн · відповідає за секунди'

export const ASSISTANT_DESCRIPTION = 'Покажу, як працює Комуналка, і відповім на ваші запитання'

export const AI_GENERATED_LABEL = 'Відповідь згенерована нашим ШІ'

export const TOPIC_SUBTITLES: Record<string, string> = {
  '/readings/create': 'Як передати та підтвердити',
  '/meters': 'Додати, тип, номер, тариф',
  '/providers': 'Постачальники та тарифи',
  '/addresses': 'Додати та керувати',
}

export const WIDGET_GREETING =
  'Привіт! Я ХаткоБот — ваш помічник. Запитайте мене про показання, лічильники, провайдерів, адреси чи тарифи.'

export const WIDGET_QUICK_CHIPS: string[] = [
  'Як внести показання?',
  'Керування лічильниками',
  'Обрати провайдера',
  'Як рахуються тарифи?',
]

export const WIDGET_FOOTER_LINK_LABEL = 'Відкрити повну сторінку допомоги →'

export const PAGE_INPUT_PLACEHOLDER = 'Запитайте про будь-який розділ Комуналки…'

export const WIDGET_INPUT_PLACEHOLDER = 'Напишіть питання…'

export const ATTACH_PHOTO_TITLE = 'Додати фото лічильника'

export const REMOVE_PHOTO_LABEL = 'Прибрати фото'

export const PHOTO_PRIVACY_NOTICE =
  'Фото надсилається AI-провайдеру для розпізнавання. Ми видаляємо з копії дані EXIF і геолокацію. Непідтверджене фото видаляється через 24 години.'

export const TEXT_PRIVACY_NOTICE = 'Запит обробляє AI-провайдер. Не надсилайте паролі, коди чи платіжні дані.'

export const PHOTO_MAX_SIZE_BYTES = 10 * 1024 * 1024

export const PHOTO_ACCEPTED_MIME_TYPES: readonly string[] = [
  'image/jpeg',
  'image/png',
  'image/webp',
  'image/heic',
  'image/heif',
]

export const PHOTO_ACCEPTED_EXTENSIONS: readonly string[] = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif']

export const PHOTO_FILE_INPUT_ACCEPT = [
  ...PHOTO_ACCEPTED_MIME_TYPES,
  ...PHOTO_ACCEPTED_EXTENSIONS.map((extension) => `.${extension}`),
].join(',')

export const PHOTO_TOO_LARGE_TEXT = 'Фото завелике. Максимальний розмір — 10 МБ.'

export const PHOTO_UNSUPPORTED_TEXT = 'Фото має бути у форматі JPG, PNG, WebP або HEIC.'

export const FALLBACK_ERROR_TEXT = 'Не вдалося отримати відповідь. Спробуйте ще раз.'

export const PHOTO_FALLBACK_ERROR_TEXT =
  'Не вдалося обробити фото. Спробуйте ще раз або внесіть показання вручну.'

export const RATE_LIMIT_ERROR_TEXT = 'Забагато запитів. Зачекайте хвилину й спробуйте ще раз.'

export const FORBIDDEN_CONVERSATION_TEXT = 'Ця розмова недоступна. Почніть нову розмову.'

export const STALE_APPROVAL_TEXT = 'Цей запит на підтвердження вже неактуальний.'

export const THINKING_PHOTO_LABEL = 'Розпізнаю фото…'

export const THINKING_SAVE_LABEL = 'Зберігаю показання…'

export const APPROVAL_LABELS = {
  title: 'Перевірте показання',
  value: 'Показання',
  date: 'Дата',
  dateFromExif: 'з EXIF',
  dateManual: 'вказано вручну',
  shotDate: 'дата зйомки',
  tariff: 'Тарифна зона',
  previous: 'Попереднє',
  initialValue: 'початкове значення',
  consumption: 'Споживання',
  consumptionPending: 'буде перераховано після підтвердження',
  negativeConsumption: 'Значення менше попереднього',
  approve: 'Підтвердити',
  saveChanges: 'Зберегти зміни',
  edit: 'Виправити',
  cancelEdit: 'Не змінювати',
  reject: 'Скасувати',
  expired: 'Строк підтвердження минув',
  stale: 'Запит більше неактуальний',
  resolved: 'Опрацьовано',
  invalidValue: 'Вкажіть коректне значення показника (число, не менше нуля).',
  invalidDate: 'Вкажіть коректну дату показання, не пізніше сьогодні.',
} as const

export const READING_RESULT_LABELS = {
  savedTitle: 'Показання збережено',
  consumption: 'споживання',
  viewReadings: 'Переглянути показання',
  rejected: 'Нічого не збережено',
} as const
