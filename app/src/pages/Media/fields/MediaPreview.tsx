import {Skeleton} from "@mantine/core";
import classes from "@/components/EntityForm/fields/ImageField.module.css";
import {iconExternal} from "@/components/EntityForm/fields/mediaBlock/icons.ts";

/** Превью оригинала: по наведению — оверлей со ссылкой на оригинал в новой вкладке. */
export default function MediaPreview({url, mime}: { url?: string; mime?: string }) {
  if (!url) {
    return <Skeleton height={240}/>;
  }

  const isVideo = mime?.startsWith("video/");
  const mediaClass = `${classes.img} ${classes.imgFit}`;

  return (
    <div className={`${classes.preview} ${classes.previewFit}`}>
      {isVideo
        ? <video src={url} controls className={mediaClass}/>
        : <img src={url} alt="" className={mediaClass}/>
      }

      {isVideo ? (
        /* У видео оверлей на всю площадь перекрыл бы контролы — только кнопка в углу */
        <div className={classes.topActions}>
          <a
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            className={classes.topActionBtn}
            title="Открыть оригинал"
            dangerouslySetInnerHTML={{__html: iconExternal}}
          />
        </div>
      ) : (
        <a
          href={url}
          target="_blank"
          rel="noopener noreferrer"
          className={classes.overlay}
          title="Открыть оригинал"
        >
          <span className={classes.overlayIcon} dangerouslySetInnerHTML={{__html: iconExternal}}/>
          <span className={classes.overlayText}>Открыть оригинал</span>
        </a>
      )}
    </div>
  );
}