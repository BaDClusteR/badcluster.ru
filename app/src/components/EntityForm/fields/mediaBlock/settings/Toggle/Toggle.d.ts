export default function Toggle({ value, icon, onChange, label }: {
    value: boolean;
    onChange: (checked: boolean) => void;
    icon?: string;
    label: string;
}): HTMLElement;
