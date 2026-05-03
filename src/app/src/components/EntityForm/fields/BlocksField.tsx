import React, { useEffect, useRef } from 'react';
import { Box, Text } from '@mantine/core';
import EditorJS, { type OutputData, type ToolConstructable } from '@editorjs/editorjs';
import { HeadingBlock } from './heading/HeadingBlock';
import { QuoteBlock } from './quote/QuoteBlock';
import { MediaBlock } from './mediaBlock/MediaBlock';
import { GalleryBlock } from './mediaBlock/GalleryBlock';
import { TerminalBlock } from './terminal/TerminalBlock';
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
  const editorRef = useRef<EditorJS | null>(null);
  const isInternalChange = useRef(false);

  // When value changes externally (e.g. form.initialize()), re-render the editor
  useEffect(() => {
    const editor = editorRef.current;
    if (!editor || !value || isInternalChange.current) {
      isInternalChange.current = false;
      return;
    }
    editor.isReady.then(() => {
      editor.render(value);
    });
  }, [value]);

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

    const editor = editorRef.current = new EditorJS({
      holder,
      data: value ?? undefined,
      placeholder: placeholder ?? false,
      tools: {
        header: {
          class: HeadingBlock as unknown as ToolConstructable,
          config: {
            levels: [2, 3, 4],
            defaultLevel: 2,
          },
        },
        quote: {
          class: QuoteBlock as unknown as ToolConstructable,
          inlineToolbar: true,
        },
        media: MediaBlock as unknown as ToolConstructable,
        gallery: GalleryBlock as unknown as ToolConstructable,
        terminal: {
          class: TerminalBlock as unknown as ToolConstructable,
          inlineToolbar: ['bold', 'italic', 'link'],
        },
      },
      i18n: {
        messages: {
          ui: {
            "blockTunes": {
              "toggler": {
                "Click to tune": "Нажмите, чтобы настроить",
                "or drag to move": "или перетащите"
              },
            },
            "inlineToolbar": {
              "converter": {
                "Convert to": "Конвертировать в"
              }
            },
            "toolbar": {
              "toolbox": {
                "Add": "Добавить",
              }
            },
            "popover": {
              "Filter": "Поиск",
              "Nothing found": "Ничего не найдено",
              "Convert to": "Конвертировать в",
            }
          },
          toolNames: {
            "Text": "Параграф",
            "Heading": "Заголовок",
            "Quote": "Цитата",
            "Link": "Ссылка",
            "Bold": "Полужирный",
            "Italic": "Курсив",
            "InlineCode": "Моноширинный",
            "Image": "Картинка"
          },
          tools: {
            "link": {
              "Add a link": "Вставьте ссылку"
            },
            "stub": {
              'The block can not be displayed correctly.': 'Блок не может быть отображен'
            },
            "linkTool": {
              "Link": "Ссылка",
              "Couldn't fetch the link data": "Не удалось получить данные",
              "Couldn't get this link data, try the other one": "Не удалось получить данные по ссылке, попробуйте другую",
              "Wrong response format from the server": "Неполадки на сервере",
            },
            "header": {
              "Heading 1": "Заголовок 1",
              "Heading 2": "Заголовок 2",
              "Heading 3": "Заголовок 3",
              "Heading 4": "Заголовок 4",
              "Heading 5": "Заголовок 5",
              "Heading 6": "Заголовок 6",
            },
            "paragraph": {
              "Enter something": "Введите текст"
            },
            "convertTo": {
              "Convert to": "Конвертировать в"
            },
          },
          blockTunes: {
            "delete": {
              "Delete": "Удалить",
              "Click to delete": "Точно удалить?"
            },
            "moveUp": {
              "Move up": "Переместить вверх"
            },
            "moveDown": {
              "Move down": "Переместить вниз"
            }
          },
        }
      },
      async onChange(api) {
        const saved = await api.saver.save();
        isInternalChange.current = true;
        onChangeRef.current(saved);
      },
    });

    return () => {
      editorRef.current = null;
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
