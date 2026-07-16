import {Anchor} from "@mantine/core";
import {notify} from "@/lib/notify.ts";

// A single expired token fails several parallel API calls at once —
// don't stack identical warnings.
const REPEAT_TIMEOUT_MS = 5000;

let lastShownAt = 0;

export default function showAuthExpiredWarning() {
    const now = Date.now();

    if (now - lastShownAt < REPEAT_TIMEOUT_MS) {
        return;
    }

    lastShownAt = now;

    notify.warning(
        "Авторизация протухла",
        <>
            Надо перелогиниться{" "}
            <Anchor href="/login/notbot" target="_blank" rel="noopener" size="sm">
                тут
            </Anchor>.
        </>
    );
}
