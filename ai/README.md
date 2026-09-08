# 主题 AI 目录

这里用于组织主题运行时 AI 的角色说明和提示词。它与根目录给编程助手看的 `AGENTS.md` 不同。

- [agents/](agents/README.md)：角色职责和输入输出边界。
- [prompts/](prompts/README.md)：提示词来源与后续拆分规范。

当前这些 Markdown 是维护说明，不会被主题自动读取或发送给模型。现有提示词仍由 `inc/inc-ai.php` 和 `inc/inc-pet.php` 构建；此次目录整理不改变 AI 请求行为。

API Key 继续使用现有后台配置机制。此目录不存放密钥、用户对话、私人资料或运行缓存。
