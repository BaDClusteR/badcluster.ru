import {Optional, StringKeyObject} from "@/types.ts";

export class HttpError extends Error {
    public readonly status: number;
    public readonly payload: StringKeyObject;
    public isHandled: boolean;

    constructor(status: number, payload: Optional<StringKeyObject>, message?: string) {
        super(message || `HTTP Error ${status}`);

        this.status = status;
        this.payload = payload ?? {};
        this.isHandled = false;
    }
}
