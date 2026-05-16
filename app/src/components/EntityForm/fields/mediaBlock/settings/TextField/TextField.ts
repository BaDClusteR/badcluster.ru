import classes from "./TextField.module.css";

export default function TextField(
  {
    value,
    onChange,
    placeholder,
    icon,
    multiline
  }: {
    value: string,
    onChange: (value: string) => void,
    placeholder?: string,
    icon?: string,
    /** Render as auto-growing textarea with word wrap (still single-line value, no Enter). */
    multiline?: boolean
  }
): HTMLElement {
  const inputWrapper = document.createElement("div");
  inputWrapper.classList.add(
    "cdx-search-field",
    classes.wrapper
  );

  if (icon) {
    inputWrapper.innerHTML = icon;
  }

  if (multiline) {
    const textarea = document.createElement("textarea");
    textarea.classList.add(
      "cdx-search-field__input",
      classes.textField,
      classes.textFieldMultiline
    );
    textarea.placeholder = String(placeholder || "");
    textarea.value = value;
    textarea.rows = 1;

    const autoResize = () => {
      textarea.style.height = "auto";
      textarea.style.height = `${textarea.scrollHeight}px`;
    };

    // Block Enter
    textarea.addEventListener("keydown", (e) => {
      if (e.key === "Enter") e.preventDefault();
    });

    // Strip newlines (from paste, etc.) and notify
    textarea.addEventListener("input", () => {
      const cleaned = textarea.value.replace(/[\r\n]+/g, " ");
      if (cleaned !== textarea.value) {
        textarea.value = cleaned;
      }
      autoResize();
      if (onChange) {
        onChange(textarea.value);
      }
    });

    inputWrapper.appendChild(textarea);
    requestAnimationFrame(autoResize);
  } else {
    const input = document.createElement("input");
    input.classList.add(
      "cdx-search-field__input",
      classes.textField
    );
    input.placeholder = String(placeholder || "");
    input.value = value;
    inputWrapper.appendChild(input);

    input.addEventListener("input", () => {
      if (onChange) {
        onChange(input.value);
      }
    });
  }

  return inputWrapper;
}
