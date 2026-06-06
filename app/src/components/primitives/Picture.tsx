import type {ReactNode} from "react";
import type {Media, MediaThumbnail, Optional} from "@admin/types";

function renderSource(thumb: Optional<MediaThumbnail>) {
  if (!thumb) return null;
  return <source srcSet={thumb.url} width={thumb.width} height={thumb.height} type={thumb.mime}/>;
}

interface PictureProps {
  media: Optional<Media>;
  /** Minimum thumbnail width to use. Defaults to 200. */
  width?: number;
  /** What to render when media is null/undefined. Defaults to null. */
  fallback?: ReactNode;
  className?: string;
  imgClassName?: string;
}

export default function Picture(
  {
    media,
    width = 200,
    fallback = null,
    className,
    imgClassName,
  }: PictureProps
): ReactNode {
  if (!media) return fallback;

  const sources: Record<string, MediaThumbnail> = {};

  media.thumbs?.forEach((thumb) => {
    if (
      thumb.width >= width
      && (!sources[thumb.mime] || sources[thumb.mime].width > thumb.width)
    ) {
      sources[thumb.mime] = thumb;
    }
  });

  return (
    <picture className={className}>
      {renderSource(sources["image/avif"])}
      {renderSource(sources["image/webp"])}
      <img
        className={imgClassName}
        src={media.url}
        width={media.width}
        height={media.height}
        alt={media.alt}
      />
    </picture>
  );
}