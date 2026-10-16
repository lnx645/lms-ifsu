import LinkExternalController from '@/actions/App/Http/Controllers/LinkExternalController';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import type { User } from '@/types/auth';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Determine whether the given user has the provided role.
 */
export function isRole(user: Pick<User, 'role'> | null | undefined, role: User['role']): boolean {
    return user?.role === role;
}


export const linkExternal = (link : string)=>{
    return `${LinkExternalController.link().url}?link=${link}`;
}

/**
 * Normalize a Select option value (number, { value }, or numeric string)
 * into a number, or null when it cannot be parsed.
 */
export function extractId(value: unknown): number | null {
    if (typeof value === 'number') {
        return value;
    }
    if (value && typeof value === 'object') {
        const v = (value as { value?: unknown }).value;
        return typeof v === 'number' ? v : null;
    }
    if (typeof value === 'string') {
        const n = Number(value);
        return Number.isNaN(n) ? null : n;
    }
    return null;
}