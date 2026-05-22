import React from 'react';
import { type OutputData } from '@editorjs/editorjs';
import { Optional } from "@admin/types";
interface BlocksFieldProps {
    label?: string;
    description?: React.ReactNode;
    placeholder?: string;
    value: Optional<OutputData>;
    onChange: (data: OutputData) => void;
    className?: string;
}
/**
 * Editor.js wrapper — block-based editor whose output is a JSON document
 * shaped like `{ blocks: [{ type, data }, ...] }`. Designed to be rendered
 * into HTML / Markdown / other targets by custom renderers.
 *
 * Adding a new block type:
 *   1. npm install @editorjs/<plugin>
 *   2. Import it here and add to the `tools` map.
 */
export declare function BlocksField({ label, description, placeholder, value, onChange, className }: BlocksFieldProps): import("react/jsx-runtime").JSX.Element;
export {};
