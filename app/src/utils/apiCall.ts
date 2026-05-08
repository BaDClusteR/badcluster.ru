import {StringKeyObject} from "@/types";
import {ApiCallMethod, ApiCallOptions} from "./types";
import {HttpError} from "@/utils/errors.ts";
import showApiError from "@/utils/showApiError.tsx";

export default async function apiCall(
    method: ApiCallMethod,
    endpoint: string,
    data: StringKeyObject,
    options?: ApiCallOptions
): Promise<StringKeyObject> {
    const headers: {[key: string]: string} = {};
    let fetchUrl = `/admin/api/${endpoint}`;

    const fetchParams: RequestInit = {
        method,
        headers
    };

    if (method === 'GET') {
        const params = new URLSearchParams();
        Object.keys(data).forEach(key => {
            params.set(key, data[key]);
        });
        fetchUrl += `?${params.toString()}`;
    } else {
        headers['Content-Type'] = 'application/json';
        fetchParams.body = JSON.stringify(data);
        if (options?.signal) {
            fetchParams.signal = options.signal;
        }
    }

    const response = await fetch(fetchUrl, fetchParams);

    if (!response.ok) {
        const errorPayload = await response.json().catch(() => null);
        const error = new HttpError(response.status, errorPayload);

        if (errorPayload) {
            showApiError(errorPayload);
            error.isHandled = true;
        }

        throw error;
    }

    try {
        return await response.json();
    } catch (e: any) {
        throw new HttpError(400, null, `Error while converting API response from JSON: ${e.message}`);
    }
}
