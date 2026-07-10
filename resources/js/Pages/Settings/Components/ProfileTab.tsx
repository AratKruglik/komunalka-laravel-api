import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Camera, Mail, Phone, User } from 'lucide-react';
import {
    Button,
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    FormMessage,
    Input,
    Label,
    PhotoDropzone,
} from '@/Components/ui';
import { resolveMediaSrc } from '@/lib/media';
import { useAuthUser } from '@/lib/useAuthUser';

export function ProfileTab() {
    const user = useAuthUser();

    const { data, setData, post, transform, processing, errors } = useForm({
        first_name: user?.firstName ?? '',
        last_name: user?.lastName ?? '',
        phone_number: user?.phoneNumber ?? '',
        avatar: null as File | null,
    });

    // Only the freshly-picked (not-yet-uploaded) file's blob preview is local state.
    // The saved avatar's src is derived from `user` on every render (below), so a
    // poll/reload response that flips `is_processing` to false is reflected immediately
    // instead of being stuck at whatever this state was seeded to on mount.
    const [selectedFileBlobUrl, setSelectedFileBlobUrl] = useState<string | null>(null);

    const savedAvatarSrc = resolveMediaSrc(user?.avatar, 'optimized_url');
    const avatarPreview = selectedFileBlobUrl ?? savedAvatarSrc;

    const handleAvatarSelected = (files: FileList | null) => {
        const file = files?.[0] ?? null;
        setData('avatar', file);

        if (file) {
            setSelectedFileBlobUrl(URL.createObjectURL(file));
        }
    };

    const handleAvatarClear = () => {
        setData('avatar', null);
        setSelectedFileBlobUrl(null);
    };

    const handleSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        transform((currentData) => ({ ...currentData, _method: 'put' }));
        post(route('settings.profile'), {
            forceFormData: true,
        });
    };

    return (
        <form onSubmit={handleSubmit}>
            <Card className="shadow-lg">
                <CardHeader>
                    <CardTitle>Особисті дані</CardTitle>
                    <CardDescription>Оновіть вашу персональну інформацію</CardDescription>
                </CardHeader>
                <CardContent className="space-y-5">
                    <div className="space-y-2">
                        <Label htmlFor="avatar">Фото профілю</Label>
                        <PhotoDropzone
                            id="avatar"
                            previewUrl={avatarPreview}
                            fileName={data.avatar?.name}
                            emptyIcon={<Camera className="h-8 w-8 text-muted" />}
                            emptyTitle="Завантажте фото"
                            emptyDescription="JPG, PNG, GIF або HEIC до 2 МБ"
                            buttonLabel="Обрати фото"
                            onFilesSelected={handleAvatarSelected}
                            onClear={handleAvatarClear}
                            inputProps={{ accept: 'image/jpeg,image/png,image/gif,image/heic,image/heif' }}
                            previewHeight={200}
                            isProcessing={!selectedFileBlobUrl && (user?.avatar?.is_processing ?? false)}
                        />
                        {errors.avatar ? <FormMessage variant="error">{errors.avatar}</FormMessage> : null}
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="first_name">Ім&apos;я</Label>
                            <Input
                                id="first_name"
                                value={data.first_name}
                                onChange={(e) => setData('first_name', e.target.value)}
                                leadingIcon={<User className="h-4 w-4 text-muted" />}
                                placeholder="Ваше ім'я"
                                disabled={processing}
                                isInvalid={Boolean(errors.first_name)}
                            />
                            {errors.first_name ? <FormMessage variant="error">{errors.first_name}</FormMessage> : null}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="last_name">Прізвище</Label>
                            <Input
                                id="last_name"
                                value={data.last_name}
                                onChange={(e) => setData('last_name', e.target.value)}
                                leadingIcon={<User className="h-4 w-4 text-muted" />}
                                placeholder="Ваше прізвище"
                                disabled={processing}
                                isInvalid={Boolean(errors.last_name)}
                            />
                            {errors.last_name ? <FormMessage variant="error">{errors.last_name}</FormMessage> : null}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email">Електронна пошта</Label>
                        <Input
                            id="email"
                            type="email"
                            value={user?.email ?? ''}
                            leadingIcon={<Mail className="h-4 w-4 text-muted" />}
                            disabled
                            readOnly
                        />
                        <p className="text-xs text-muted">
                            Електронну пошту неможливо змінити
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="phone_number">Номер телефону</Label>
                        <Input
                            id="phone_number"
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            leadingIcon={<Phone className="h-4 w-4 text-muted" />}
                            placeholder="+380 XX XXX XX XX"
                            disabled={processing}
                            isInvalid={Boolean(errors.phone_number)}
                        />
                        {errors.phone_number ? <FormMessage variant="error">{errors.phone_number}</FormMessage> : null}
                    </div>

                    <div className="flex justify-end pt-2">
                        <Button type="submit" loading={processing} loadingText="Збереження...">
                            Зберегти зміни
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}
