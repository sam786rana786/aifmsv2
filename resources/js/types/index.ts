export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    phone?: string;
    employee_id?: string;
    profile_picture?: string;
    school_id?: number;
    is_active: boolean;
    last_login_at?: string;
    last_login_ip?: string;
    last_login_user_agent?: string;
    created_at: string;
    updated_at: string;
    permissions?: string[];
    roles?: string[];
}

export interface BreadcrumbItem {
    label: string;
    href?: string;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
    };
    name: string;
    quote: {
        message: string;
        author: string;
    };
    settings: Record<string, any>;
    notifications: any[];
    ziggy: {
        location: string;
        [key: string]: any;
    };
    sidebarOpen: boolean;
}; 