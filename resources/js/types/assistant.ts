export interface AssistantSource {
  label: string
  path: string
}

export interface AssistantMessage {
  id: string
  role: 'user' | 'assistant'
  text: string
  sources?: AssistantSource[]
}

export interface AskAssistantResponse {
  text: string
  sources: AssistantSource[]
}

export interface TopicShortcut {
  label: string
  path: string
}
