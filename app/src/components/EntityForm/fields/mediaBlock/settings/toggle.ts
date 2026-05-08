export default function Toggle(
    {
        value,
        icon,
        onChange,
        label
    }: {
        value: boolean,
        onChange: (checked: boolean) => void,
        icon?: string,
        label: string
    }
): HTMLElement {
    const settingWrapper = document.createElement('div');
    settingWrapper.classList.add(
        'cdx-settings-button',
        'cdx-settings-button--custom'
    );
    settingWrapper.classList.toggle('cdx-settings-button--active', value);

    if (icon) {
        const iconWrapper = document.createElement('div');
        iconWrapper.classList.add('ce-popover-item__icon', 'ce-popover-item__icon--tool');
        iconWrapper.innerHTML = icon;
        settingWrapper.appendChild(iconWrapper);
    }

    const title = document.createElement('div');
    title.classList.add('ce-popover-item__title');
    title.innerHTML = label;

    settingWrapper.appendChild(title);

    settingWrapper.addEventListener('click', () => {
        value = !value;

        if (onChange) {
            onChange(value);
        }

        settingWrapper.classList.toggle('cdx-settings-button--active', value);
    });

    return settingWrapper;
}
