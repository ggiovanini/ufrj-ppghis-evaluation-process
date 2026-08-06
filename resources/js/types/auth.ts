export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Role = {
    role: 'admin' | 'reviewer' | 'master_committee' | 'doctorate_committee';
};

export type Auth = {
    user: User;
    roles: Role[];
    permissions: Permission[];
    currentSelectionProcess: any;
    is_impersonating: boolean;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type Permission = 'projects.import'
    | 'projects.view'
    | 'projects.manage'
    | 'review.assign'
    | 'review.evaluate'
    | 'review.submit'
    | 'review.update'
    | 'review.view-own'
    | 'review.results.view'
    | 'review.results.calculate'
    | 'written-exam.record'
    | 'committee.manage'
    | 'committee.assign-members'
    | 'committee.evaluate'
    | 'committee.submit'
    | 'committee.update'
    | 'committee.results.view'
    | 'committee.finalize'
    | 'results.view'
    | 'results.publish'
    | 'users.manage';

export function authCan(auth: Auth, permission: Permission): boolean {
    return auth.permissions.includes(permission);
}
