import {StringKeyObject} from "@/types.ts";

export default function deepClone(obj: StringKeyObject): StringKeyObject {
    return JSON.parse(
        JSON.stringify(obj)
    );
}
