export function useNetwork() {
    let isOnline = $state(true);

    $effect(() => {
        isOnline = navigator.onLine;

        const handleOnline = () => (isOnline = true);
        const handleOffline = () => (isOnline = false);
        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);
        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    });
    return {
        get isOnline() {
            return isOnline;
        }
    };
}