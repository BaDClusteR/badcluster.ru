import {useEffect, useRef, useState} from "react";
import {Text, Stack} from "@mantine/core";
import {EditorView, basicSetup} from "codemirror";
import {json} from "@codemirror/lang-json";
import {oneDark} from "@codemirror/theme-one-dark";
import {EditorState, Compartment} from "@codemirror/state";
import classes from "./JsonField.module.css";

interface JsonFieldProps {
  label?: string;
  description?: React.ReactNode;
  withAsterisk?: boolean;
  error?: React.ReactNode;
  value?: unknown;
  onChange: (value: string) => void;
  /** Editor height. Defaults to 300. */
  height?: number;
}

function isDarkMode(): boolean {
  return document.documentElement.getAttribute("data-mantine-color-scheme") === "dark";
}

export function JsonField({
  label,
  description,
  withAsterisk,
  error,
  value = "",
  onChange,
  height = 300,
}: JsonFieldProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const viewRef = useRef<EditorView | null>(null);
  const themeCompartment = useRef(new Compartment());
  const onChangeRef = useRef(onChange);
  onChangeRef.current = onChange;
  const isInternalChange = useRef(false);
  const [parseError, setParseError] = useState<string | null>(null);

  // Mount editor
  useEffect(() => {
    if (!containerRef.current) return;

    const state = EditorState.create({
      doc: formatJson(normalizeValue(value)),
      extensions: [
        basicSetup,
        json(),
        EditorView.updateListener.of((update) => {
          if (update.docChanged) {
            const text = update.state.doc.toString();
            isInternalChange.current = true;
            onChangeRef.current(text);
            try {
              JSON.parse(text);
              setParseError(null);
            } catch (e: any) {
              setParseError(e.message);
            }
          }
        }),
        EditorView.theme({
          "&": {
            height: `${height}px`,
            border: "1px solid var(--color-border)",
            borderRadius: "var(--border-radius)",
            fontSize: "13px",
          },
          ".cm-scroller": {
            overflow: "auto",
          },
        }),
        themeCompartment.current.of(isDarkMode() ? oneDark : []),
      ],
    });

    const view = new EditorView({
      state,
      parent: containerRef.current,
    });

    viewRef.current = view;

    // Watch for theme changes via MutationObserver
    const observer = new MutationObserver(() => {
      view.dispatch({
        effects: themeCompartment.current.reconfigure(
          isDarkMode() ? oneDark : []
        ),
      });
    });

    observer.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ["data-mantine-color-scheme"],
    });

    return () => {
      observer.disconnect();
      view.destroy();
      viewRef.current = null;
    };
  }, []);

  // Sync external value changes (e.g. form.initialize())
  const valueStr = normalizeValue(value);
  useEffect(() => {
    const view = viewRef.current;
    if (!view || isInternalChange.current) {
      isInternalChange.current = false;
      return;
    }

    const current = view.state.doc.toString();
    const formatted = formatJson(valueStr);
    if (formatted !== current) {
      isInternalChange.current = true;
      view.dispatch({
        changes: {from: 0, to: current.length, insert: formatted},
      });
    }
  }, [valueStr]);

  return (
    <Stack gap={4}>
      {label && (
        <Text size="sm" fw={500}>
          {label}
          {withAsterisk && <span className={classes.asterisk}> *</span>}
        </Text>
      )}
      {description && <Text size="xs" c="dimmed">{description}</Text>}
      <div ref={containerRef} className={classes.editor}/>
      {(parseError || error) && (
        <Text size="xs" c="red">{parseError || error}</Text>
      )}
    </Stack>
  );
}

/** Ensure value is always a string — backend may return a parsed object. */
function normalizeValue(value: unknown): string {
  if (typeof value === "string") return value;
  if (value == null) return "";
  try {
    return JSON.stringify(value, null, 2);
  } catch {
    return String(value);
  }
}

function formatJson(value: string): string {
  if (!value) return value;
  try {
    return JSON.stringify(JSON.parse(value), null, 2);
  } catch {
    return value;
  }
}