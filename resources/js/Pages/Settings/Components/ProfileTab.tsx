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
import { useAuthUser } from '@/lib/useAuthUser';

export function ProfileTab() {
    const user = useAuthUser();

    const { data, setData, put, processing, errors } = useForm({
        first_name: user?.firstName ?? '',
        last_name: user?.lastName ?? '',
        phone_number: user?.phoneNumber ?? '',
        avatar: null as File | null,
    });

    const [avatarPreview, setAvatarPreview] = useState<string | null>(
        user?.avatarOptimizedUrl ?? null,
    );

    const handleAvatarSelected = (files: FileList | null) => {
        const file = files?.[0] ?? null;
        setData('avatar', file);

        if (file) {
            const url = URL.createObjectURL(file);
            setAvatarPreview(url);
        }
    };

    const handleAvatarClear = () => {
        setData('avatar', null);
        setAvatarPreview(user?.avatarOptimizedUrl ?? null);
    };

    const handleSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        put(route('settings.profile'), {
            forceFormData: true,
        });
    };

    return (
        <form onSubmit={handleSubmit}>
            <Card className="border border-gray-200 shadow-lg dark:border-slate-800">
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
                            emptyIcon={<Camera className="h-8 w-8 text-gray-400" />}
                            emptyTitle="Завантажте фото"
                            emptyDescription="JPG, PNG, GIF або HEIC до 2 МБ"
                            buttonLabel="Обрати фото"
                            onFilesSelected={handleAvatarSelected}
                            onClear={handleAvatarClear}
                            inputProps={{ accept: 'image/jpeg,image/png,image/gif,image/heic,image/heif' }}
                            previewHeight={200}
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
                                leadingIcon={<User className="h-4 w-4 text-neutral-500" />}
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
                                leadingIcon={<User className="h-4 w-4 text-neutral-500" />}
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
                            leadingIcon={<Mail className="h-4 w-4 text-neutral-500" />}
                            disabled
                            readOnly
                        />
                        <p className="text-xs text-gray-500 dark:text-slate-400">
                            Електронну пошту неможливо змінити
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="phone_number">Номер телефону</Label>
                        <Input
                            id="phone_number"
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            leadingIcon={<Phone className="h-4 w-4 text-neutral-500" />}
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
