import React, {useEffect, useState} from "react";
import {Button as MantineButton, Checkbox, Group, ScrollArea, Stack, Text} from "@mantine/core";
import Modal from "@/components/primitives/Modal.tsx";
import Button from "@/components/primitives/Button.tsx";
import type {Suggestion} from "@/lib/typography";
import classes from "./TypographyReview.module.css";

/**
 * Context-dependent «ё» found by the typographer: the author ticks the ones to fix.
 */
export function TypographyReview(
  {
    suggestions,
    onApply,
    onClose
  }: {
    /** null — the modal is closed. */
    suggestions: Suggestion[] | null,
    onApply: (accepted: Suggestion[], rejected: Suggestion[], rememberRejected: boolean) => void,
    onClose: () => void
  }
): React.JSX.Element {
  const [accepted, setAccepted] = useState<Set<number>>(new Set());
  const [rememberRejected, setRememberRejected] = useState(true);

  useEffect(() => {
    setAccepted(new Set());
  }, [suggestions]);

  const list = suggestions ?? [];

  const toggle = (index: number) => {
    setAccepted((prev) => {
      const next = new Set(prev);
      if (next.has(index)) {
        next.delete(index);
      } else {
        next.add(index);
      }
      return next;
    });
  };

  const apply = () => {
    onApply(
      list.filter((_, i) => accepted.has(i)),
      list.filter((_, i) => !accepted.has(i)),
      rememberRejected
    );
  };

  return (
    <Modal
      opened={suggestions !== null}
      onClose={onClose}
      title={`Типограф: спорные «ё» (${list.length})`}
      size="xl"
    >
      <Stack gap="sm">
        <Text size="sm" c="dimmed">
          Отметьте места, где нужна «ё». Однозначные исправления уже сделаны автоматически.
        </Text>
        <Group gap="xs">
          <MantineButton size="xs" variant="subtle" onClick={() => setAccepted(new Set(list.map((_, i) => i)))}>
            Отметить все
          </MantineButton>
          <MantineButton size="xs" variant="subtle" onClick={() => setAccepted(new Set())}>
            Снять все
          </MantineButton>
        </Group>
        <ScrollArea.Autosize mah="60vh">
          <Stack gap={4}>
            {list.map((s, i) => (
              <label key={`${s.field}-${JSON.stringify(s.path)}-${s.offset}`} className={classes.row}>
                <Checkbox
                  checked={accepted.has(i)}
                  onChange={() => toggle(i)}
                  className={classes.checkbox}
                />
                <span className={classes.context}>
                  {s.truncatedBefore && "…"}{s.contextBefore}
                  <mark className={accepted.has(i) ? classes.wordAccepted : classes.word}>
                    {accepted.has(i) ? s.after : s.before}
                  </mark>
                  {s.contextAfter}{s.truncatedAfter && "…"}
                </span>
                <span className={classes.replacement}>{s.before} → {s.after}</span>
              </label>
            ))}
          </Stack>
        </ScrollArea.Autosize>
        <Group justify="space-between">
          <Checkbox
            size="xs"
            label="Не показывать неотмеченные снова"
            checked={rememberRejected}
            onChange={(e) => setRememberRejected(e.currentTarget.checked)}
          />
          <Group gap="xs">
            <Button variant="default" onClick={onClose}>Отмена</Button>
            <Button onClick={apply}>
              {accepted.size > 0 ? `Применить (${accepted.size})` : "Готово"}
            </Button>
          </Group>
        </Group>
      </Stack>
    </Modal>
  );
}
