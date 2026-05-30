import {Optional, StringKeyObject} from "@admin/types";
import {ReactNode} from "react";
import classes from "./GameHero.module.css";
import {Textarea} from "@mantine/core";
import {FormErrors, UseFormReturnType} from "@mantine/form";
import {GameMaterial, MaterialGame} from "../../types";
import formatDate from "../utils";

export default function GameHero(
  {
    game,
    form
  }: {
    game: Optional<MaterialGame>,
    form: UseFormReturnType<GameMaterial, GameMaterial, (values: GameMaterial) => FormErrors>
  }
): ReactNode {
  const titleProps = form.getInputProps("title");
  let cover = null;
  if (game?.cover) {
    const formats: StringKeyObject = {
      "image/webp": null,
      "image/avif": null
    };

    game.cover.thumbs?.forEach((item) => {
      if (
        item.width >= 200
        && (
          !formats[item.mime]
          || formats[item.mime].width > item.width
        )
      ) {
        formats[item.mime] = item;
      }
    });

    cover = <picture className={classes.gameHeroPicture}>
      {
        formats["image/avif"] &&
        <source
          srcSet={formats["image/avif"].url}
          width={formats["image/avif"].width}
          height={formats["image/avif"].height}
          type="image/avif"
        />
      }
      {
        formats["image/webp"] &&
        <source
          srcSet={formats["image/webp"].url}
          width={formats["image/webp"].width}
          height={formats["image/webp"].height}
          type="image/webp"
        />
      }
      <img src={game.cover.url} width={game.cover.width} height={game.cover.height} alt={game.cover.alt}/>
    </picture>;
  }
  return <header className={classes.gameHero} data-mantine-color-scheme="dark">
    {cover}
    <div className={classes.gameHeroContent}>
      {game && <span className={classes.gameHeroGameName}>{game.title}</span>}
      <Textarea
        required
        autosize
        placeholder="Заголовок материала"
        {...titleProps}
        onKeyDown={
          (e) => {
            if (e.key === "Enter") {
              e.preventDefault();
            }
          }
        }
        onChange={
          (e) => {
            e.target.value = e.target.value.replace(/[\r\n]+/gm, " ");
            titleProps.onChange(e);
          }
        }
      />
      <div className={classes.gameHeroMeta}>
        {
          form.values.dateAdded &&
          <span className={classes.gameHeroTag}>
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24">
              <path
                fill="currentColor"
                d="M8 5.75c-.41 0-.75-.34-.75-.75V2c0-.41.34-.75.75-.75s.75.34.75.75v3c0 .41-.34.75-.75.75M16 5.75c-.41 0-.75-.34-.75-.75V2c0-.41.34-.75.75-.75s.75.34.75.75v3c0 .41-.34.75-.75.75M20.5 9.84h-17c-.41 0-.75-.34-.75-.75s.34-.75.75-.75h17c.41 0 .75.34.75.75s-.34.75-.75.75"
              />
              <path
                fill="currentColor"
                d="M16 22.75H8c-3.65 0-5.75-2.1-5.75-5.75V8.5c0-3.65 2.1-5.75 5.75-5.75h8c3.65 0 5.75 2.1 5.75 5.75V17c0 3.65-2.1 5.75-5.75 5.75M8 4.25c-2.86 0-4.25 1.39-4.25 4.25V17c0 2.86 1.39 4.25 4.25 4.25h8c2.86 0 4.25-1.39 4.25-4.25V8.5c0-2.86-1.39-4.25-4.25-4.25z"
              />
              <path
                fill="currentColor"
                d="M8.5 14.5c-.13 0-.26-.03-.38-.08q-.18-.075-.33-.21-.135-.15-.21-.33a1 1 0 0 1-.08-.38c0-.26.11-.52.29-.71q.15-.135.33-.21c.18-.08.38-.1.58-.06q.09.015.18.06.09.03.18.09l.15.12c.04.05.09.1.12.15q.06.09.09.18.045.09.06.18c.01.07.02.13.02.2 0 .26-.11.52-.29.71-.19.18-.45.29-.71.29M12 14.5c-.26 0-.52-.11-.71-.29l-.12-.15a.8.8 0 0 1-.09-.18.6.6 0 0 1-.06-.18c-.01-.07-.02-.13-.02-.2 0-.13.03-.26.08-.38q.075-.18.21-.33c.28-.28.73-.37 1.09-.21.13.05.23.12.33.21.18.19.29.45.29.71 0 .07-.01.13-.02.2q-.015.09-.06.18-.03.09-.09.18l-.12.15c-.1.09-.2.16-.33.21-.12.05-.25.08-.38.08M8.5 18c-.13 0-.26-.03-.38-.08q-.18-.075-.33-.21c-.09-.1-.16-.2-.21-.33A1 1 0 0 1 7.5 17c0-.26.11-.52.29-.71q.15-.135.33-.21c.37-.16.81-.07 1.09.21.04.05.09.1.12.15q.06.09.09.18c.03.06.05.12.06.19.01.06.02.13.02.19 0 .26-.11.52-.29.71-.19.18-.45.29-.71.29"
              />
          </svg>
            {formatDate(form.values.dateAdded)}
        </span>
        }
        {
          form.values.shortTitle &&
          <span className={classes.gameHeroTag}>Сохранения</span>
        }
      </div>
    </div>
  </header>;
}
