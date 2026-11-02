import { router } from '@inertiajs/react';
import { useConnectionStatus, useEcho } from '@laravel/echo-react';
import { useCallback, useEffect, useRef } from 'react';

const DASHBOARD_LIVE_PROPS = ['stats', 'consumptionHistory', 'expenseDistribution', 'recentReadings'];
const RELOAD_DEBOUNCE_MS = 400;

function useCoalescedReload(): () => void {
    const timerRef = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);
    const inFlightRef = useRef(false);
    const dirtyRef = useRef(false);
    const unmountedRef = useRef(false);

    const reload = useCallback(() => {
        inFlightRef.current = true;
        router.reload({
            only: DASHBOARD_LIVE_PROPS,
            onFinish: () => {
                inFlightRef.current = false;
                if (dirtyRef.current && !unmountedRef.current) {
                    dirtyRef.current = false;
                    reload();
                }
            },
        });
    }, []);

    useEffect(() => {
        unmountedRef.current = false;

        return () => {
            unmountedRef.current = true;
            clearTimeout(timerRef.current);
        };
    }, []);

    return useCallback(() => {
        clearTimeout(timerRef.current);
        timerRef.current = setTimeout(() => {
            if (inFlightRef.current) {
                dirtyRef.current = true;
                return;
            }
            reload();
        }, RELOAD_DEBOUNCE_MS);
    }, [reload]);
}

interface AddressChannelListenerProps {
    addressId: number;
    onChange: () => void;
}

function AddressChannelListener({ addressId, onChange }: AddressChannelListenerProps) {
    useEcho(`address.${addressId}`, '.meter-readings.changed', onChange);

    return null;
}

interface DashboardLiveUpdatesProps {
    addressIds: number[];
}

export function DashboardLiveUpdates({ addressIds }: DashboardLiveUpdatesProps) {
    const scheduleReload = useCoalescedReload();
    const status = useConnectionStatus();
    const hasConnectedRef = useRef(false);

    useEffect(() => {
        if (status !== 'connected') {
            return;
        }
        if (hasConnectedRef.current) {
            scheduleReload();
        }
        hasConnectedRef.current = true;
    }, [status, scheduleReload]);

    return (
        <>
            {addressIds.map((id) => (
                <AddressChannelListener key={id} addressId={id} onChange={scheduleReload} />
            ))}
        </>
    );
}
