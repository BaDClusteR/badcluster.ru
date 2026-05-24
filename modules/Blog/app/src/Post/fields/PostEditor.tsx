import {MultiSelect, Skeleton, Textarea} from "@mantine/core";
import classes from "./PostEditor.module.css";
import placeholders from "../placeholders";
import {FormErrors, UseFormReturnType} from "@mantine/form";
import {BlogPostContext, Post} from "../types";
import {EntityFormRenderOptions} from "@admin/types";
import React from "react";

export default function PostEditor(
  {
    form,
    options
  }: {
    form: UseFormReturnType<Post, Post, (values: Post) => FormErrors>,
    options: EntityFormRenderOptions<BlogPostContext>
  }
): React.JSX.Element {
  const tags = options?.context?.tags;
  const {BlocksField, FieldGroup} = options!.components;
  const titleProps = form.getInputProps("title");
  return <>
    <FieldGroup isSubmitting={form.submitting}>
      <Skeleton visible={options?.loading}>
        <Textarea
          autosize
          placeholder="Заголовок поста"
          {...titleProps}
          classNames={{input: classes.postTitle}}
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
        <MultiSelect
          classNames={{
            root: classes.tagListRoot,
            input: classes.tagListInput,
            section: classes.tagListSection,
            pill: classes.tagListPill
          }}
          placeholder="Тэги"
          data={tags ?? []}
          value={tags?.length ? (form.values.tags as string[] ?? []) : []}
          onChange={(values: string[]) => {
            form.setFieldValue("tags", values as never);
          }}
        />
        <BlocksField
          className={classes.contentInline}
          value={form.values.content as never}
          placeholder={placeholders[Math.floor(Math.random() * placeholders.length)]}
          onChange={(data) => {
            form.setFieldValue("content", data as never)
          }}
        />
      </Skeleton>
    </FieldGroup>
  </>;
}
