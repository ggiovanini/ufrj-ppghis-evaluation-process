export interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    links: PaginationMetaLink[];
    path: string;
    per_page: number;
    to: number;
    total: number;
}

export interface Resource<T> {
    data: T;
}

export interface ResourceCollection<T> {
    data: T[];
}

export interface PaginationMetaLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginationLinks {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
}

export interface DataPagination<T> {
    data: T[];
    links: PaginationLinks;
    meta: PaginationMeta;
}

export interface DataFilters {
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
}
