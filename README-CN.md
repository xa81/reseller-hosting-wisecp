<div align="center">
  <a href="README-TR.md">TR <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/TR.png" alt="TR" height="20" /></a>
  <a href="README.md"> | EN <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/US.png" alt="EN" height="20" /></a>
  <a href="README-DE.md"> | DE <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/DE.png" alt="DE" height="20" /></a>
  <a href="README-SA.md"> | AR <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/SA.png" alt="AR" height="20" /></a>
  <a href="README-NL.md"> | NL <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/NL.png" alt="NL" height="20" /></a>
  <a href="README-AZ.md"> | AZ <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/AZ.png" alt="AZ" height="20" /></a>
  <a href="README-CN.md"> | CN <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/CN.png" alt="CN" height="20" /></a>
  <a href="README-FR.md"> | FR <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/FR.png" alt="FR" height="20" /></a>
  <a href="README-IT.md"> | IT <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/IT.png" alt="IT" height="20" /></a>
  <a href="README-RU.md"> | RU <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/RU.png" alt="RU" height="20" /></a>
  <a href="README-ES.md"> | ES <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/ES.png" alt="ES" height="20" /></a>
</div>

<div align="center">

# DNA Reseller Hosting

**用一个 WiseCP 服务器模块管理 cPanel 与 Plesk 经销商账户。**

一个模块，两种面板。面板类型无需你选择 —— 模块会亲自询问服务器，并记住是哪种面板作出了响应。

![WiseCP](https://img.shields.io/badge/WiseCP-5.x%20%7C%203.x-4A90D9?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![cPanel](https://img.shields.io/badge/cPanel%2FWHM-%E6%94%AF%E6%8C%81-FF6C2C?style=flat-square)
![Plesk](https://img.shields.io/badge/Plesk-%E6%94%AF%E6%8C%81-53BCE6?style=flat-square)

</div>

---

## 📑 目录

- [✨ 功能一览](#-功能一览)
- [📋 环境要求](#-环境要求)
- [🚀 安装](#-安装)
- [🔍 日志与故障排查](#-日志与故障排查)
- [🧩 须知事项](#-须知事项)
- [📄 更新日志](#-更新日志)

---

## ✨ 功能一览

| 功能 | cPanel/WHM | Plesk |
|---|:---:|:---:|
| 连接测试与面板自动识别 | ✅ | ✅ |
| 创建账户（可安全重试） | ✅ | ✅ |
| 暂停 / 恢复 | ✅ | ✅ |
| 删除 | ✅ | ✅ *(校验归属)* |
| 修改密码 | ✅ | ✅ |
| 更换套餐 / 方案（升级及服务页面） | ✅ | ✅ |
| 服务概览中的磁盘与流量仪表 | ✅ | ✅ |
| 用于计量计费的磁盘与流量用量 | ✅ | ✅ |
| 一键登录控制面板 | ✅ | ✅ |

> 💡 **为经销商而设计。** 无需 root 或管理员权限；模块以你自己的经销商账户权限运行，它创建的每个账户都会
> 占用你的配额。

---

## 📋 环境要求

- 自建的 **WiseCP 5** 或 **WiseCP 3**，并且你拥有管理员权限；两者各有自己的安装包
- **PHP**：WiseCP 5 上为 8.2 或更高版本，这是 WiseCP 5 的要求；WiseCP 3 上为 7.4 – 8.4
- PHP 扩展：WiseCP 5 本身已要求的扩展，外加 `simplexml`（标准 PHP 构建中默认启用）
- 一个 **cPanel/WHM 经销商账户**（WHM API 令牌）&nbsp;或&nbsp;一个 **Plesk 经销商账户**
  （secret key 或面板密码）
- 从 WiseCP 服务器到面板服务器、面板 API 端口的出站访问

> ✅ 不创建任何数据库表，不需要 cron，也没有 composer 依赖。
> 除了你所配置的面板服务器之外，不会向任何地方发送任何数据。

---

## 🚀 安装

### 1️⃣ 安装模块

每个版本都附带两个安装包。请选择与你的 WiseCP 版本对应的那个：

| WiseCP | 安装包 | 本仓库中的源码 |
|---|---|---|
| **5.x** | `DNAHosting-WiseCP5-v2.x.x.zip` | `v5/coremio/` |
| **3.x** | `DNAHosting-WiseCP3-v1.x.x.zip` | `v3/coremio/` |

> ⚠️ **两个安装包不能互换使用。** WiseCP 5 修改了服务器模块的接口约定，因此 WiseCP 3 模块无法在
> WiseCP 5 上加载，反之亦然。

在模块管理器中上传安装包，或将其解压到 WiseCP 安装目录的根目录。压缩包内含完整路径，因此模块会落在：

```
wisecp/
└── coremio/
    └── modules/
        └── Servers/
            └── DNAHosting/     ← 放这里
```

以下步骤适用于 **WiseCP 5**。WiseCP 3 中的界面名称不同；参见 *须知事项* 中的
[WiseCP 3](#-须知事项)。

### 2️⃣ 添加服务器

**Products → Hosting/Server → Server Settings → Add New Server。** 在 **Server Automation Module** 下的
**Web Hosting** 选项卡中，选择 **DNA Reseller Hosting**（列在 *Other* 下）。

| 字段 | 填写内容 |
|---|---|
| **Address** | 面板服务器。保持 **https://** 开关开启。优先使用面板证书覆盖的主机名；IP 地址只有在证书覆盖该 IP 时才能通过证书验证 |
| **Port** | cPanel 会预填 `2087`。Plesk 请点击 **Use Custom Port** 并填写 `8443` |
| **Verify SSL Certificate** | 保持开启。仅当面板使用自签名证书时才关闭 |
| **Username** | 你在该面板上的经销商用户名 |
| **Password** | **cPanel：** 以该经销商身份创建的 WHM API 令牌。**Plesk：** secret key，或面板密码 |
| **Hostname** | 面板服务器的主机名；WiseCP 也会在服务器和服务记录中显示它。当它是 `server.example.com` 这样的完整主机名时，模块会连接它而不是 **Address**，因此即使 **Address** 填的是 IP，证书验证也能正常进行 |

![WiseCP 5 中的服务器设置](https://raw.githubusercontent.com/domainreseller/reseller-hosting-wisecp/main/docs/images/v5-server-settings.png)

### 3️⃣ 测试连接

点击 **Test Connection**。绿色结果同时确认凭据和识别出的面板；失败时会显示 HTTP 状态码或面板自身的错误。
参见 [日志与故障排查](#-日志与故障排查)。

### 4️⃣ 创建服务器组 *(可选)*

如果有多台服务器，请在 **Server Groups** 下创建分组，并把产品绑定到分组而不是单台服务器。

> ⚠️ **同一分组内请保持面板类型一致。** 产品表单中的套餐列表来自一台服务器。如果分组里同时有 cPanel 和
> Plesk，你选择的名称在另一种面板上可能不存在，落到该服务器的订单会以 *找不到套餐* 失败。模块保存的是套餐的
> **名称**，而不是面板中的 ID，因此在分组内每台服务器上都存在的名称，在所有服务器上都能正常使用。

### 5️⃣ 配置产品

创建主机产品，并在 **Module Assignment** 中选择 **DNA Reseller Hosting**。在产品的 **Automation** 选项卡中，
选择 **Single Server**（或服务器分组）以及服务器。随后模块会绘制一个字段：

| 设置 | 取值 |
|---|---|
| **Package / Plan** | 从服务器实时拉取的列表；识别出的面板会显示在标签中，例如 *Package / Plan (cPanel / WHM)*。如果无法连接服务器，该字段会变成文本框，并在下方显示原因 |

> 💡 **cPanel 上无需理会套餐前缀。** 在面板中显示为 `reseller_basic` 的套餐可以填写为 `basic`；
> 模块会自行解析前缀。

> ℹ️ 同一选项卡中的 **Resource Limits** 不会发送到面板。账户的磁盘、带宽及其他限制来自所选的套餐或方案；
> WiseCP 在服务页面上也会说明这一点。

**保存 —— 模块即可使用。** 🎉

---

## 🔍 日志与故障排查

在 WiseCP 中开启模块日志期间，模块发送到面板的每个请求及其返回的响应都会写入**模块日志**。每条记录都标注了
对应的面板调用，例如 `createacct` 或 `webspace.add`。

> 🔐 服务器的令牌或密码、模块生成或修改的每个账户密码，以及单点登录会话令牌，无论在请求还是响应中，
> 都会在**写入任何内容之前**用 `***` 掩码。

### 常见错误

| 现象 | 原因与解决 |
|---|---|
| `HTTP 403`，或 *服务器以 cPanel 用户 API 作出了响应* | 令牌背后的经销商对该调用没有 WHM 权限。请在 **WHM → Resellers → Edit Reseller's ACL List** 中授予账户列表、创建、暂停、删除、密码、更换套餐、套餐列表、流量与会话权限，然后**以该经销商身份**创建令牌 |
| 证书错误 | 证书未覆盖你填写的地址。请填写证书所覆盖的主机名，或对使用自签名证书的面板关闭 **Verify SSL Certificate** |
| Plesk **11003** | secret key 是为另一个 IP 签发的。请为 WiseCP 实际连接所用的地址新建一个，或改用面板密码 |
| *出于安全考虑已拒绝：订阅 … 并非由此服务创建* | 在 Plesk 上，模块只对它为该服务创建的客户进行操作。该订阅是手工创建的，或由另一个服务创建 |

---

## 🧩 须知事项

<details>
<summary><b>重试是安全的</b></summary>

<br>

WiseCP 的模块队列会重新执行失败的创建操作。模块在调用面板之前就已保存用户名和加密后的密码，因此重试时会
复用它们：已经创建但响应丢失的账户会被直接接管，而不会以 *已存在* 失败。删除一个已经不存在的账户会被视为
已完成。

</details>

<details>
<summary><b>两种面板的差异</b></summary>

<br>

- **Plesk 上的每项操作都会校验归属。** 模块创建的客户以服务编号作为其外部 ID，任何操作之前都会检查该标记。
  模块绝不会对手工创建的订阅进行操作。
- **修改在用 Plesk 服务的域名**不会同步到面板：Plesk 订阅是按域名查找的，因此之后的操作会报告订阅不存在。
  请同时在 Plesk 中重命名该订阅。在 cPanel 上，在你于面板中修改之前，账户会继续服务旧域名。
- **重复域名保护。** 只要同一台服务器上还有另一个活动或暂停的服务关联着同一个域名，删除就会被拒绝。

</details>

<details>
<summary><b>面板识别</b></summary>

<br>

面板类型从不需要配置。首次调用时模块会探测服务器，并在一周内记住是哪种面板作出了响应；**Test Connection**
则始终会重新探测。

端口只决定**先**尝试哪种面板：`8443` 与 `8880` 先试 Plesk，其他端口先试 cPanel。猜错只会让识别变慢，
并不会让它失效。识别过程中，模块也会把所配置的凭据发送到**同一**台服务器上另一种面板的 API
端点；它从不连接任何其他主机。

</details>

<details>
<summary><b>凭据</b></summary>

<br>

两种面板的凭据都填在 **Password** 字段，WiseCP 会加密保存。**Access Hash** 字段以明文保存，因此本模块
不提供该字段，也从不读取它。在 Plesk 上，模块会先把凭据当作 secret key 尝试，失败后自行回退到登录名与密码。

</details>

<details>
<summary><b>WiseCP 3</b></summary>

<br>

WiseCP 3 安装包（`v3/coremio/`）在 WiseCP 3 自己的界面上提供同样的开通功能：

1. **Products / Services → Hosting/Server → Shared Server Settings → Add New Shared Server。**
   将 **Server Automation Type** 选为 `DNAHosting`，并填写 **IP Address**、**Username**、**Password**
   （WHM API 令牌、Plesk secret key 或 Plesk 密码）、**Connect with SSL** 和 **Port**
   （cPanel 为 `2087`，Plesk 为 `8443`）。那里的 **Hostname** 字段只是一个标签；模块连接的是
   **IP Address**。点击 **Test Connection**。
2. **Products / Services → Hosting/Server → Web Hosting Packages** → 打开套餐 → **Module
   Settings** 选项卡。选择服务器或服务器组；模块会显示 **Detected panel** 以及
   **Package / Plan** 列表。

与 WiseCP 5 安装包的差异：

- WiseCP 3 没有为服务器模块提供证书验证开关。当服务器的 **Hostname** 是面板证书所覆盖的完整主机名时，
  安装包会连接该主机名并验证证书；如果只有 IP 地址，则连接该 IP 且不做验证。
- 客户区显示磁盘和带宽用量，以及一个登录面板的按钮；WiseCP 5 的服务概览卡片在 WiseCP 3 上不存在。
- 在 Plesk 上更改运行中服务的域名会被拒绝，而不是交给运营者处理。

</details>

<details>
<summary><b>不在范围内</b></summary>

<br>

邮箱、数据库与文件管理工具，销售经销商账户，把既有账户导入 WiseCP，以及 *以服务器管理员身份登录* 按钮。
模块只持有**经销商**凭据，绝不持有 root 凭据。导入功能是有意不提供的：导入的 Plesk 订阅不会带有本模块的
归属标记，之后对它的每一项操作都会被拒绝。

</details>

---

## 📄 更新日志

各版本的变更记录见 [CHANGELOG.md](CHANGELOG.md)。

---

<div align="center">

**DNA Reseller Hosting** · 适用于 WiseCP 的 cPanel & Plesk 经销商模块

[domainnameapi.com](https://www.domainnameapi.com) · [经销商面板](https://dm.domainnameapi.com/hosting)

</div>
