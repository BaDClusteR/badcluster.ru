import {StringKeyObject} from "@admin/types";

export default function deepClone(obj: StringKeyObject): StringKeyObject {
    return JSON.parse(
        JSON.stringify(obj)
    );
}
