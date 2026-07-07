import {Skeleton} from "@mantine/core";

/** Превью оригинала — просто показывает картинку (или видео), без действий над ней. */
export default function MediaPreview({url, mime}: { url?: string; mime?: string }) {
  if (!url) {
    return <Skeleton height={240}/>;
  }

  const style: React.CSSProperties = {
    display: "block",
    maxWidth: "100%",
    height: "auto",
    borderRadius: "var(--border-radius)"
  };

  return mime?.startsWith("video/")
    ? <video src={url} controls style={style}/>
    : <img src={url} alt="" style={style}/>;
}