import {Anchor, Stack, Text} from "@mantine/core";
import {MediaThumb} from "../types";

export default function MediaThumbnails({thumbs}: { thumbs?: MediaThumb[] }) {
  return (
    <Stack gap={4}>
      <Text size="sm" fw={500}>Тамбнейлы</Text>
      {!thumbs?.length && <Text size="xs" c="dimmed">Тамбнейлов нет</Text>}
      {thumbs?.map((thumb) => (
        <Anchor
          key={thumb.id}
          href={thumb.url}
          target="_blank"
          rel="noopener noreferrer"
          size="sm"
        >
          {thumb.filename}, {thumb.mime} ({thumb.width}×{thumb.height}, {thumb.sizeHumanReadable})
        </Anchor>
      ))}
    </Stack>
  );
}