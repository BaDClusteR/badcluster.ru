import {Skeleton} from "@mantine/core";
import classes from "./MaterialContent.module.css";
import {FormErrors, UseFormReturnType} from "@mantine/form";
import {GameMaterial, GameMaterialContext, MaterialGame} from "../../types";
import {EntityFormRenderOptions, Optional} from "@admin/types";
import React from "react";
import FileBlock from "../FileBlock";
import GameHero from "../GameHero";

export default function MaterialContent(
  {
    form,
    options
  }: {
    form: UseFormReturnType<GameMaterial, GameMaterial, (values: GameMaterial) => FormErrors>,
    options: EntityFormRenderOptions<GameMaterialContext>
  }
): React.JSX.Element {
  let selectedGame: Optional<MaterialGame> = undefined;
  if (options.context?.games) {
    options.context.games.forEach(
      game => {
        if (game.id === form.values.gameId) {
          selectedGame = game;
        }
      }
    );
  }
  const {BlocksField, FieldGroup} = options!.components;
  return <FieldGroup isSubmitting={form.submitting} focusMode={false} className={classes.container}>
    <Skeleton visible={options?.loading} style={{marginBlockEnd: "calc(1.5 * var(--rhythmic-unit))", zIndex: 2}}>
      <GameHero game={selectedGame} form={form}/>
    </Skeleton>
    <Skeleton visible={options?.loading}>
      <BlocksField
        className={classes.description}
        value={form.values.description}
        showSettings={false}
        placeholder={"Описание"}
        onChange={(data) => {
          form.setFieldValue("description", data as never);
        }}
      />
      <FileBlock file={form.values.file} dateAdded={form.values.dateAdded}/>
      <BlocksField
        className={classes.setupInstructions}
        value={form.values.setupInstructions}
        showSettings={false}
        placeholder={"Инструкции по установке"}
        onChange={(data) => {
          form.setFieldValue("setupInstructions", data as never);
        }}
      />
    </Skeleton>
  </FieldGroup>;
}
