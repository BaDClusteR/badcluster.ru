import {Card, Image, Text, Group, ActionIcon} from "@mantine/core";
import {IconTrash, IconExternalLink} from "@tabler/icons-react";
import type {ReactNode} from "react";
import type {EntityRow} from "@admin/types";
import type {MediaGridController} from "./MediaGrid";

export interface MediaItem extends EntityRow {
  url: string,
  width: number,
  height: number,
  mime: string,
  alt: string,
}

export interface MediaCardProps<T extends MediaItem> {
  item: T;
  ctrl: MediaGridController<T>;
  /**
   * Содержимое инфо-блока под картинкой (слева от кнопок).
   * По умолчанию — #id, размеры и mime-тип.
   */
  info?: ReactNode;
}

export function MediaCard<T extends MediaItem>({item, ctrl, info}: MediaCardProps<T>) {
  return (
    <Card shadow="xs" padding="xs" radius="md" withBorder>
      <Card.Section
        style={{cursor: "pointer"}}
        onClick={() => ctrl.navigateToEdit(item)}
      >
        {item.mime?.startsWith("video/") ? (
          <video
            src={item.url}
            preload="metadata"
            muted
            playsInline
            style={{display: "block", width: "100%", height: 140, objectFit: "cover"}}
          />
        ) : (
          <Image src={item.url} height={140} alt={`#${item.id}`}/>
        )}
      </Card.Section>

      <Group justify="space-between" mt="xs">
        <div>
          {info ?? (
            <>
              <Text size="sm" fw={500}>#{item.id}</Text>
              <Text size="xs" c="dimmed">{item.width}×{item.height} · {item.mime}</Text>
            </>
          )}
        </div>
        <Group gap={4}>
          <ActionIcon
            variant="subtle"
            size="sm"
            component="a"
            href={item.url}
            target="_blank"
          >
            <IconExternalLink size={14}/>
          </ActionIcon>
          {ctrl.permissions.delete && (
            <ActionIcon
              variant="subtle"
              color="red"
              size="sm"
              onClick={() => ctrl.confirmDeletion([item])}
            >
              <IconTrash size={14}/>
            </ActionIcon>
          )}
        </Group>
      </Group>
    </Card>
  );
}