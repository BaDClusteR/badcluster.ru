import classes from "./textfield.module.css";

export default function TextField(
    {
        value,
        onChange,
        placeholder
    }: {
        value: string,
        onChange: (value: string) => void,
        placeholder?: string
    }
): HTMLElement {
    const inputWrapper = document.createElement('div');
    inputWrapper.classList.add(
        'cdx-search-field',
        classes.wrapper
    );
    const input = document.createElement('input');
    input.classList.add(
        'cdx-search-field__input',
        classes.textField
    );
    input.placeholder = String(placeholder || '');
    input.value = value;
    inputWrapper.appendChild(input);

    // Handle data changes
    input.addEventListener('input', () => {
        if (onChange) {
            onChange(input.value);
        }
    });

    return inputWrapper;
}
