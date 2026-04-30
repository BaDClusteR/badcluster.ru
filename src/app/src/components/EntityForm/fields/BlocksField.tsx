import React, { useEffect, useRef } from 'react';
import { Box, Text } from '@mantine/core';
import EditorJS, { type OutputData, type ToolConstructable } from '@editorjs/editorjs';
import Header from '@editorjs/header';
import List from '@editorjs/list';
import Quote from '@editorjs/quote';
import { MediaBlock } from './mediaBlock/MediaBlock';
import { GalleryBlock } from './mediaBlock/GalleryBlock';
import classes from './BlocksField.module.css';
import "./editorjs.css";
import {Optional} from "@/types.ts";
import clsx from "clsx";

interface BlocksFieldProps {
  label?: string,
  description?: React.ReactNode,
  placeholder?: string,
  value: Optional<OutputData>,
  onChange: (data: OutputData) => void,
  className?: string,
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
export function BlocksField({ label, description, placeholder, value, onChange, className }: BlocksFieldProps) {
  const wrapperRef = useRef<HTMLDivElement>(null);
  const onChangeRef = useRef(onChange);
  onChangeRef.current = onChange;

  useEffect(() => {
    const wrapper = wrapperRef.current;
    if (!wrapper) return;

    // Create a fresh inner holder per mount. Editor.js's async `destroy()`
    // clears its holder's innerHTML; if we reused the same node across
    // StrictMode's double-mount, the first instance's late destroy would
    // wipe the second instance's UI (the editor stays alive in memory but
    // its DOM disappears). Isolating holders fixes it permanently.
    const holder = document.createElement('div');
    wrapper.appendChild(holder);

    const editor = new EditorJS({
      holder,
      data: value ?? undefined,
      placeholder: placeholder ?? false,
      tools: {
        header: {
          class: Header as unknown as ToolConstructable,
          config: { levels: [2, 3, 4], defaultLevel: 2 },
        },
        list: {
          class: List as unknown as ToolConstructable,
          inlineToolbar: true,
        },
        quote: Quote as unknown as ToolConstructable,
        media: MediaBlock as unknown as ToolConstructable,
        gallery: GalleryBlock as unknown as ToolConstructable,
      },
      async onChange(api) {
        const saved = await api.saver.save();
        onChangeRef.current(saved);
      },
    });

    return () => {
      editor.isReady
        .then(() => {
          editor.destroy();
          holder.remove();
        })
        .catch(() => {
          holder.remove();
        });
    };
    // Mount once — re-mounting on every value change would wipe the editor.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <Box>
      <Text size="sm" fw={500} mb={4}>
        {label}
      </Text>
      {description && (
        <Text size="xs" c="dimmed" mb={6}>
          {description}
        </Text>
      )}
      <div ref={wrapperRef} className={clsx(classes.editor, className)} />
    </Box>
  );
}
