/**
 * Suggestions the author decided to keep as is («все» meaning "everyone").
 * Remembered in localStorage so the same spot isn't offered again.
 */
const STORAGE_KEY = "bc.typography.dismissed";
const LIMIT = 5000;

function read(): string[] {
  try {
    const value = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? "[]");

    return Array.isArray(value) ? value : [];
  } catch {
    return [];
  }
}

export function getDismissed(): Set<string> {
  return new Set(read());
}

export function dismiss(keys: string[]) {
  if (keys.length === 0) {
    return;
  }

  const all = [...new Set([...read(), ...keys])].slice(-LIMIT);

  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(all));
  } catch {
    // Storage is full or unavailable — the suggestion will simply show up again.
  }
}
