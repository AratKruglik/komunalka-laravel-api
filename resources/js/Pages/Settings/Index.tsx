import { Head, router } from '@inertiajs/react';
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout';
import { SettingsSidebar } from './Components/SettingsSidebar';
import { ProfileTab } from './Components/ProfileTab';
import { SecurityTab } from './Components/SecurityTab';
import { AppearanceTab } from './Components/AppearanceTab';
import { AccountTab } from './Components/AccountTab';
import type { PageProps } from '@/types';

type SettingsTab = 'profile' | 'security' | 'appearance' | 'account';

interface ConnectedProvider {
    provider: string;
    is_connected: boolean;
}

interface Props extends PageProps {
    tab: string;
    connectedProviders: ConnectedProvider[];
    hasPassword: boolean;
}

const VALID_TABS: SettingsTab[] = ['profile', 'security', 'appearance', 'account'];

function resolveTab(raw: string): SettingsTab {
    return VALID_TABS.includes(raw as SettingsTab) ? (raw as SettingsTab) : 'profile';
}

export default function Index({ tab, connectedProviders, hasPassword }: Props) {
    const activeTab = resolveTab(tab);

    const handleTabChange = (nextTab: SettingsTab) => {
        router.get(
            route('settings', { tab: nextTab }),
            {},
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout pageTitle="Налаштування" pageSubtitle="Керуйте параметрами вашого акаунта">
            <Head title="Налаштування" />

            <div className="flex flex-col gap-6 md:flex-row md:gap-8">
                <aside className="shrink-0 md:sticky md:top-20 md:w-56 md:self-start">
                    <SettingsSidebar activeTab={activeTab} onTabChange={handleTabChange} />
                </aside>

                <div className="min-w-0 flex-1" role="tabpanel">
                    {activeTab === 'profile' && <ProfileTab />}
                    {activeTab === 'security' && <SecurityTab connectedProviders={connectedProviders} />}
                    {activeTab === 'appearance' && <AppearanceTab />}
                    {activeTab === 'account' && <AccountTab hasPassword={hasPassword} />}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
