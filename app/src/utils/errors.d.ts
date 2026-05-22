import { Optional, StringKeyObject } from "@admin/types";
export declare class HttpError extends Error {
    readonly status: number;
    readonly payload: StringKeyObject;
    isHandled: boolean;
    constructor(status: number, payload: Optional<StringKeyObject>, message?: string);
}
