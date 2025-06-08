import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

// Date utilities
export function formatDate(date: string | Date, format: string = 'MMM dd, yyyy'): string {
    const d = new Date(date);
    if (isNaN(d.getTime())) return '';
    
    const months = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    
    const day = d.getDate();
    const month = months[d.getMonth()];
    const year = d.getFullYear();
    
    return `${month} ${day}, ${year}`;
}

export function formatCurrency(amount: number, currency: string = 'USD', symbol: string = '$'): string {
    return `${symbol}${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export function formatPercentage(value: number): string {
    return `${value.toFixed(2)}%`;
}

// String utilities
export function capitalize(str: string): string {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

export function truncate(str: string, length: number = 50): string {
    if (str.length <= length) return str;
    return str.substring(0, length) + '...';
}

export function slugify(str: string): string {
    return str
        .toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

// Number utilities
export function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Validation utilities
export function isValidEmail(email: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

export function isValidPhone(phone: string): boolean {
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
}

// Array utilities
export function groupBy<T>(array: T[], key: keyof T): Record<string, T[]> {
    return array.reduce((groups, item) => {
        const group = String(item[key]);
        groups[group] = groups[group] || [];
        groups[group].push(item);
        return groups;
    }, {} as Record<string, T[]>);
}

export function unique<T>(array: T[]): T[] {
    return [...new Set(array)];
}

// Color utilities for status badges
export function getStatusColor(status: string): string {
    const statusColors: Record<string, string> = {
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-gray-100 text-gray-800',
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        paid: 'bg-green-100 text-green-800',
        unpaid: 'bg-red-100 text-red-800',
        partial: 'bg-yellow-100 text-yellow-800',
        overdue: 'bg-red-100 text-red-800',
        present: 'bg-green-100 text-green-800',
        absent: 'bg-red-100 text-red-800',
        late: 'bg-yellow-100 text-yellow-800',
    };
    
    return statusColors[status.toLowerCase()] || 'bg-gray-100 text-gray-800';
}

// Local storage utilities
export function getFromStorage(key: string, defaultValue: any = null): any {
    try {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : defaultValue;
    } catch {
        return defaultValue;
    }
}

export function setToStorage(key: string, value: any): void {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (error) {
        console.error('Failed to save to localStorage:', error);
    }
}

export function removeFromStorage(key: string): void {
    try {
        localStorage.removeItem(key);
    } catch (error) {
        console.error('Failed to remove from localStorage:', error);
    }
}
