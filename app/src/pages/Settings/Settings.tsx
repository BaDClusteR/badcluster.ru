import {useEffect, useState} from "react";
import {Group, Skeleton, Stack, Text, Textarea, Title} from "@mantine/core";
import {useMutation, useQuery, useQueryClient} from "@tanstack/react-query";
import apiCall from "@/utils/apiCall";
import {notify} from "@/lib/notify";
import Button from "@/components/primitives/Button";

interface Settings {
  commentBlacklist: string;
}

const QUERY_KEY = ["settings"];

export default function SettingsPage() {
  const queryClient = useQueryClient();
  const [commentBlacklist, setCommentBlacklist] = useState("");

  const {data, isPending, isError} = useQuery({
    queryKey: QUERY_KEY,
    queryFn: async ({signal}) => await apiCall("GET", "settings", {}, {signal}) as unknown as Settings
  });

  // Значение приезжает асинхронно, поэтому поле наполняем после загрузки,
  // а не через defaultValue — иначе оно останется пустым
  useEffect(() => {
    if (data) {
      setCommentBlacklist(data.commentBlacklist ?? "");
    }
  }, [data]);

  const {mutate, isPending: isSaving} = useMutation({
    mutationFn: async () => await apiCall("PUT", "settings", {commentBlacklist}),
    onSuccess: async () => {
      await queryClient.invalidateQueries({queryKey: QUERY_KEY});
      notify.success("Сохранено", "Настройки обновлены");
    }
  });

  return (
    <>
      <Title order={2} mb="lg">Настройки</Title>

      <Stack gap="lg" maw={800}>
        {isError && <Text c="red">Не удалось загрузить настройки</Text>}

        {isPending
          ? <Skeleton height={320}/>
          : <Textarea
            label="Чёрный список для комментариев"
            description={
              "Одно слово или домен на строку. Если хоть одно встретится в тексте комментария "
              + "или в нике, комментарий молча отбрасывается: автор увидит обычный ответ, "
              + "но в базу он не попадёт и уведомление в Slack не уйдёт. "
              + "Регистр не важен. Строки, начинающиеся с #, игнорируются — в них удобно "
              + "оставлять заметки для себя."
            }
            placeholder={"# спамные домены\nexample-casino.com\nbit.ly"}
            value={commentBlacklist}
            onChange={(event) => setCommentBlacklist(event.currentTarget.value)}
            autosize
            minRows={12}
            maxRows={30}
            spellCheck={false}
            styles={{input: {fontFamily: "monospace"}}}
          />
        }

        <Group justify="flex-end">
          <Button
            onClick={() => mutate()}
            loading={isSaving}
            disabled={isPending || commentBlacklist === (data?.commentBlacklist ?? "")}
          >
            Сохранить
          </Button>
        </Group>
      </Stack>
    </>
  );
}
