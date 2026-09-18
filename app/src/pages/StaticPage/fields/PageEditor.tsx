import React from "react";
import {Skeleton, Textarea} from "@mantine/core";
import {FormErrors, UseFormReturnType} from "@mantine/form";
import {EntityFormRenderOptions} from "@admin/types";
import {StaticPage} from "../types";
import classes from "./PageEditor.module.css";

/**
 * Primary editing area: the page heading and its content.
 *
 * A custom group rather than declarative `text` + `blocks` fields, so the title
 * can be the large inline heading and the content editor can hide its settings
 * panel (there is nothing to configure — static pages have no images by default).
 */
export default function PageEditor(
  {
    form,
    options
  }: {
    form: UseFormReturnType<StaticPage, StaticPage, (values: StaticPage) => FormErrors>,
    options: EntityFormRenderOptions
  }
): React.JSX.Element {
  const {BlocksField, FieldGroup} = options!.components;
  const titleProps = form.getInputProps("title");

  return (
    <FieldGroup isSubmitting={form.submitting}>
      <Skeleton visible={options?.loading}>
        <Textarea
          autosize
          placeholder="Заголовок страницы"
          {...titleProps}
          classNames={{input: classes.pageTitle}}
          onKeyDown={(e) => {
            if (e.key === "Enter") {
              e.preventDefault();
            }
          }}
          onChange={(e) => {
            e.target.value = e.target.value.replace(/[\r\n]+/gm, " ");
            titleProps.onChange(e);
          }}
        />
        <BlocksField
          showSettings={false}
          className={classes.contentInline}
          value={form.values.content as never}
          placeholder="Текст страницы"
          description={
            <>
              Динамические вставки: <code>[site_age]</code> — возраст сайта,{" "}
              <code>[site_age_round]</code> — он же, округлённый вниз до пятёрки,{" "}
              <code>[my_age]</code> — твой возраст (работают и в заголовке страницы),{" "}
              <code>[news]</code> — блок «Хайлайты» (отдельным абзацем, без другого текста).
            </>
          }
          onChange={(data) => {
            form.setFieldValue("content", data as never);
          }}
        />
      </Skeleton>
    </FieldGroup>
  );
}
