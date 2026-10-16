// src/lib/utils/antiCheat.svelte.ts

type ViolationType = 'tab_switch' | 'window_blur' | 'forbidden_key';

export function useAntiCheat(onViolation?: (type: ViolationType, count: number) => void) {
    let violations = $state(0);
    let isFocused = $state(true);

    function recordViolation(type: ViolationType) {
        violations++;
        if (onViolation) onViolation(type, violations);
    }

    $effect(() => {
        const handleVisibilityChange = () => {
            if (document.visibilityState === 'hidden') {
                recordViolation('tab_switch');
            }
        };
        const handleBlur = () => {
            isFocused = false;
            recordViolation('window_blur');
        };

        const handleFocus = () => {
            isFocused = true;
        };
        const handleContextMenu = (e: MouseEvent) => {
            e.preventDefault();
        };
        const handleKeyDown = (e: KeyboardEvent) => {
            // F12
            if (e.key === 'F12') {
                e.preventDefault();
                recordViolation('forbidden_key');
            }
            
            // Ctrl/Cmd + Shift + I/J/C (DevTools)
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && ['I', 'i', 'J', 'j', 'C', 'c'].includes(e.key)) {
                e.preventDefault();
                recordViolation('forbidden_key');
            }

            // Ctrl/Cmd + C/V/X/P (Copy, Paste, Cut, Print)
            if ((e.ctrlKey || e.metaKey) && ['C', 'c', 'V', 'v', 'X', 'x', 'P', 'p'].includes(e.key)) {
                e.preventDefault();
                recordViolation('forbidden_key');
            }
        };
        document.addEventListener('visibilitychange', handleVisibilityChange);
        window.addEventListener('blur', handleBlur);
        window.addEventListener('focus', handleFocus);
        document.addEventListener('contextmenu', handleContextMenu);
        document.addEventListener('keydown', handleKeyDown);
        return () => {
            document.removeEventListener('visibilitychange', handleVisibilityChange);
            window.removeEventListener('blur', handleBlur);
            window.removeEventListener('focus', handleFocus);
            document.removeEventListener('contextmenu', handleContextMenu);
            document.removeEventListener('keydown', handleKeyDown);
        };
    });

    return {
        get violations() {
            return violations;
        },
        get isFocused() {
            return isFocused;
        },
        resetViolations: () => {
            violations = 0;
        }
    };
}