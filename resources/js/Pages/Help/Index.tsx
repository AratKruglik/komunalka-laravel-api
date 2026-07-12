import { useRef, useState } from 'react'
import { Head, Link } from '@inertiajs/react'
import { ChevronRight, FileText, FilePlus, Gauge, MapPin, Plug, Sparkles } from 'lucide-react'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui'
import { ChatInput } from '@/Components/help/ChatInput'
import { ChatMessageList } from '@/Components/help/ChatMessageList'
import { SuggestedChips } from '@/Components/help/SuggestedChips'
import { useAssistantChat } from '@/hooks/useAssistantChat'
import { ASSISTANT_STATUS_LABEL, ASSISTANT_TITLE, PAGE_INPUT_PLACEHOLDER, TOPIC_SUBTITLES } from '@/constants/assistant'
import type { PageProps, TopicShortcut } from '@/types'

interface Props extends PageProps {
  greeting: string
  suggestedChips: string[]
  topicShortcuts: TopicShortcut[]
  docLinks: TopicShortcut[]
  askEndpoint: string
}

const TOPIC_ICONS: Record<string, typeof Gauge> = {
  '/readings/create': FilePlus,
  '/meters': Gauge,
  '/providers': Plug,
  '/addresses': MapPin,
}

export default function Index({ greeting, suggestedChips, topicShortcuts, docLinks }: Props) {
  const [draft, setDraft] = useState('')
  const { messages, isThinking, send } = useAssistantChat(greeting)
  const textareaRef = useRef<HTMLTextAreaElement>(null)

  const handleSend = (question: string) => {
    send(question)
    setDraft('')
  }

  return (
    <AuthenticatedLayout
      pageTitle="Допомога"
      pageSubtitle="Запитайте КомуШІшку про будь-який розділ Комуналки"
      contentFillHeight
    >
      <Head title="Допомога" />

      <div className="grid grid-cols-1 gap-6 lg:min-h-0 lg:flex-1 lg:grid-cols-[1fr_320px] lg:[grid-template-rows:minmax(0,1fr)]">
        <Card className="flex h-[640px] flex-col lg:h-full lg:min-h-0">
          <CardHeader className="flex-row items-center gap-3 space-y-0 border-b border-line py-4">
            <span className="flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-full bg-primary text-on-primary">
              <Sparkles className="h-[22px] w-[22px]" />
            </span>
            <div className="min-w-0 flex-1">
              <CardTitle className="text-[15px]">{ASSISTANT_TITLE}</CardTitle>
              <p className="mt-0.5 flex items-center text-xs text-subtext">
                <span className="mr-1.5 inline-block h-[7px] w-[7px] rounded-full bg-success" />
                {ASSISTANT_STATUS_LABEL}
              </p>
            </div>
          </CardHeader>

          <CardContent className="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden">
            <ChatMessageList messages={messages} isThinking={isThinking} size="default" />
            <SuggestedChips items={suggestedChips} onSelect={handleSend} disabled={isThinking} />
            <ChatInput
              ref={textareaRef}
              value={draft}
              onChange={setDraft}
              onSend={() => handleSend(draft)}
              disabled={isThinking}
              placeholder={PAGE_INPUT_PLACEHOLDER}
            />
          </CardContent>
        </Card>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Про що можна запитати</CardTitle>
            </CardHeader>
            <CardContent className="space-y-1 py-4">
              {topicShortcuts.map((shortcut) => {
                const Icon = TOPIC_ICONS[shortcut.path] ?? Gauge
                return (
                  <Link
                    key={shortcut.path}
                    href={shortcut.path}
                    className="flex items-center gap-3 rounded-[10px] border border-transparent px-3 py-2.5 transition-colors hover:border-line hover:bg-raised"
                  >
                    <span className="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-[9px] bg-raised text-subtext">
                      <Icon className="h-[17px] w-[17px]" />
                    </span>
                    <span className="min-w-0">
                      <span className="block text-sm font-medium text-foreground">{shortcut.label}</span>
                      {TOPIC_SUBTITLES[shortcut.path] ? (
                        <span className="mt-0.5 block text-xs text-muted">{TOPIC_SUBTITLES[shortcut.path]}</span>
                      ) : null}
                    </span>
                  </Link>
                )
              })}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Документація</CardTitle>
            </CardHeader>
            <CardContent className="space-y-1 py-4">
              {docLinks.map((docLink) => (
                <Link
                  key={docLink.path}
                  href={docLink.path}
                  className="flex items-center gap-2.5 rounded-[9px] px-3 py-2.5 text-sm text-subtext transition-colors hover:bg-raised hover:text-foreground"
                >
                  <FileText className="h-4 w-4 shrink-0" />
                  <span className="flex-1">{docLink.label}</span>
                  <ChevronRight className="h-[15px] w-[15px] shrink-0 text-muted" />
                </Link>
              ))}
            </CardContent>
          </Card>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
