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

**Gestione las cuentas de hosting de revendedor de cPanel y Plesk que proporciona DomainNameAPI desde un único módulo de servidor de WiseCP.**

Un módulo, dos paneles. Usted nunca elige el tipo de panel: el módulo se lo pregunta al servidor y
recuerda qué panel respondió.

![WiseCP](https://img.shields.io/badge/WiseCP-5.x%20%7C%203.x-4A90D9?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![cPanel](https://img.shields.io/badge/cPanel%2FWHM-compatible-FF6C2C?style=flat-square)
![Plesk](https://img.shields.io/badge/Plesk-compatible-53BCE6?style=flat-square)

</div>

---

## 📑 Índice

- [✨ Qué hace](#-qué-hace)
- [📋 Requisitos](#-requisitos)
- [🚀 Instalación](#-instalación)
- [🔍 Registros y diagnóstico](#-registros-y-diagnóstico)
- [🧩 Cosas que conviene saber](#-cosas-que-conviene-saber)
- [📄 Historial de cambios](#-historial-de-cambios)

---

## ✨ Qué hace

| Función | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Prueba de conexión y detección automática del panel | ✅ | ✅ |
| Creación de cuenta (se puede reintentar con seguridad) | ✅ | ✅ |
| Suspender / reactivar | ✅ | ✅ |
| Cancelación | ✅ | ✅ *(propiedad verificada)* |
| Cambio de contraseña | ✅ | ✅ |
| Cambio de paquete / plan (mejora y pantalla del servicio) | ✅ | ✅ |
| Indicadores de disco y tráfico en el resumen del servicio | ✅ | ✅ |
| Uso de disco y tráfico para facturación por consumo | ✅ | ✅ |
| Acceso al panel de control con un clic | ✅ | ✅ |

> 💡 **Pensado para el hosting de revendedor de DomainNameAPI.** El módulo está hecho para las cuentas
> de revendedor de cPanel/WHM y Plesk que proporciona DomainNameAPI. No necesita acceso root ni de
> administrador; trabaja con los permisos de su cuenta de revendedor de DomainNameAPI, y cada cuenta
> que crea consume su cuota. ¿Aún no tiene una cuenta de hosting de revendedor? Obtenga una en
> [dm.domainnameapi.com/hosting](https://dm.domainnameapi.com/hosting).

---

## 📋 Requisitos

- **WiseCP 5** o **WiseCP 3** en su propio servidor, con acceso de administrador; cada uno tiene su propio paquete
- **PHP** 8.2 o superior en WiseCP 5, como exige WiseCP 5; 7.4 – 8.4 en WiseCP 3
- Extensiones de PHP: las que WiseCP 5 ya exige, más `simplexml` (activada en las compilaciones estándar de PHP)
- Una **cuenta de hosting de revendedor de DomainNameAPI**: cPanel/WHM (WHM API token) &nbsp;o&nbsp; Plesk
  (clave secreta o la contraseña del panel)
- Acceso saliente desde el servidor de WiseCP al servidor del panel, en el puerto de la API del panel

> ✅ No se crea ninguna tabla en la base de datos, no hace falta cron y no hay dependencias de
> composer. No se envía nada a ningún sitio salvo al servidor del panel que usted configure.

---

## 🚀 Instalación

### 1️⃣ Instale el módulo

Cada versión incluye dos paquetes. Elija el de su versión de WiseCP:

| WiseCP | Paquete | Código fuente en este repositorio |
|---|---|---|
| **5.x** | `DNAHosting-WiseCP5-v2.x.x.zip` | `v5/coremio/` |
| **3.x** | `DNAHosting-WiseCP3-v1.x.x.zip` | `v3/coremio/` |

> ⚠️ **Los paquetes no son intercambiables.** WiseCP 5 cambió el contrato de los módulos de servidor,
> así que el módulo de WiseCP 3 no se carga en WiseCP 5 y viceversa.

Suba el paquete desde el gestor de módulos, o extráigalo en la raíz de su instalación
de WiseCP. El archivo incluye la ruta completa, así que el módulo queda en:

```
wisecp/
└── coremio/
    └── modules/
        └── Servers/
            └── DNAHosting/     ← aquí
```

Los pasos siguientes son para **WiseCP 5**. En WiseCP 3 las pantallas tienen otros nombres; consulte
[WiseCP 3](#-cosas-que-conviene-saber) en *Cosas que conviene saber*.

### 2️⃣ Añada el servidor

**Products → Hosting/Server → Server Settings → Add New Server.** En **Server Automation Module**,
en la pestaña **Web Hosting**, elija **DNA Reseller Hosting** (aparece bajo *Other*).

| Campo | Qué introducir |
|---|---|
| **Address** | El servidor del panel. Mantenga activado el interruptor **https://**. Prefiera un nombre de host que el certificado del panel cubra; una dirección IP solo supera la verificación del certificado si el certificado cubre esa IP |
| **Port** | Para cPanel viene rellenado `2087`. Para Plesk, pulse **Use Custom Port** e introduzca `8443` |
| **Verify SSL Certificate** | Manténgalo activado. Desactívelo solo para un panel que use un certificado autofirmado |
| **Username** | Su usuario de revendedor en ese panel |
| **Password** | **cPanel:** un token de la API de WHM creado como ese revendedor. **Plesk:** una clave secreta, o la contraseña del panel |
| **Hostname** | El nombre de host del servidor del panel; WiseCP también lo muestra en los registros de servidores y servicios. Cuando es un nombre de host completo como `server.example.com`, el módulo se conecta a él en lugar de a **Address**, de modo que la verificación del certificado funciona aunque **Address** contenga una IP |

![Ajustes del servidor en WiseCP 5](https://raw.githubusercontent.com/domainreseller/reseller-hosting-wisecp/main/docs/images/v5-server-settings.png)

### 3️⃣ Pruebe la conexión

Pulse **Test Connection**. Un resultado verde confirma las credenciales y el panel detectado; si
falla, se muestra el código HTTP o el propio error del panel. Vea
[Registros y diagnóstico](#-registros-y-diagnóstico).

### 4️⃣ Cree un grupo de servidores *(opcional)*

Si tiene más de un servidor, cree un grupo en **Server Groups** y vincule el producto al grupo en
lugar de a un único servidor.

> ⚠️ **Mantenga cada grupo homogéneo por panel.** La lista de paquetes del formulario del producto
> procede de un solo servidor. Si un grupo mezcla cPanel y Plesk, el nombre elegido puede no existir
> en el otro panel y el pedido que caiga allí fallará con *paquete no encontrado*. El módulo guarda
> el **nombre** del paquete, no el id del panel, así que un nombre que exista en todos los
> servidores del grupo funciona en todos ellos.

### 5️⃣ Configure el producto

Cree el producto de hosting y elija **DNA Reseller Hosting** en **Module Assignment**. En la pestaña
**Automation** del producto, elija **Single Server** (o un grupo de servidores) y el servidor. El módulo
dibuja entonces un solo campo:

| Ajuste | Valor |
|---|---|
| **Package / Plan** | La lista obtenida en vivo del servidor; el panel detectado aparece en la etiqueta, por ejemplo *Package / Plan (cPanel / WHM)*. Si no se puede contactar con el servidor, el campo se convierte en un cuadro de texto con el motivo debajo |

> 💡 **No se preocupe por el prefijo del paquete en cPanel.** Un paquete que en el panel aparece como
> `reseller_basic` puede introducirse como `basic`; el módulo resuelve el prefijo por su cuenta.

> ℹ️ Los **Resource Limits** de la misma pestaña no se envían al panel. El disco, el ancho de banda y
> los demás límites de la cuenta provienen del paquete o plan que elija; WiseCP también lo indica en la
> pantalla del servicio.

**Guárdelo: el módulo ya está listo.** 🎉

---

## 🔍 Registros y diagnóstico

Cada petición que el módulo envía al panel, y la respuesta que recibe, se escribe en el
**registro del módulo** mientras el registro de módulos esté activado en WiseCP. Cada entrada se
etiqueta con la llamada al panel, como `createacct` o `webspace.add`.

> 🔐 El token o la contraseña del servidor, cualquier contraseña de cuenta que el módulo genere o
> cambie y los tokens de sesión de inicio de sesión único se enmascaran con `***` **antes de
> escribir nada**, tanto en la petición como en la respuesta.

### Errores frecuentes

| Síntoma | Causa y solución |
|---|---|
| `HTTP 403`, o *el servidor respondió con la API de usuario de cPanel* | El revendedor al que pertenece el token no tiene privilegio de WHM para esa llamada. Conceda en **WHM → Resellers → Edit Reseller's ACL List** los privilegios de listado de cuentas, creación, suspensión, cancelación, contraseña, cambio de paquete, listado de paquetes, tráfico y sesión, y después cree el token **como ese revendedor** |
| Un error de certificado | El certificado no cubre la dirección que introdujo. Introduzca el nombre de host que cubre el certificado, o desactive **Verify SSL Certificate** para un panel con certificado autofirmado |
| Plesk **11003** | La clave secreta se emitió para otra IP. Cree una nueva para la dirección desde la que conecta WiseCP, o use la contraseña del panel |
| *Rechazado por seguridad: la suscripción … no fue creada por este servicio* | En Plesk el módulo solo actúa sobre clientes que creó para ese servicio. La suscripción se creó a mano o desde otro servicio |

---

## 🧩 Cosas que conviene saber

<details>
<summary><b>Los reintentos son seguros</b></summary>

<br>

La cola de módulos de WiseCP vuelve a ejecutar una creación fallida. El módulo guarda el nombre de
usuario y la contraseña cifrada antes de llamar al panel, así que un reintento los reutiliza: una
cuenta que se creó pero cuya respuesta se perdió se adopta en lugar de fallar con *ya existe*. La
cancelación de una cuenta que ya no existe se da por completada.

</details>

<details>
<summary><b>Diferencias entre paneles</b></summary>

<br>

- **Cada operación en Plesk verifica la propiedad.** El cliente que crea el módulo lleva el número
  de servicio como id externo, y esa marca se comprueba antes de cualquier operación. El módulo
  nunca puede actuar sobre una suscripción creada a mano.
- **Cambiar el dominio de un servicio de Plesk en marcha** no se traslada al panel: una suscripción
  de Plesk se localiza por su dominio, así que las operaciones posteriores indicarán que no existe
  ninguna suscripción. Cambie también el nombre de la suscripción en Plesk. En cPanel la cuenta sigue
  sirviendo el dominio antiguo hasta que lo cambie en el panel.
- **Protección contra dominios duplicados.** La cancelación se rechaza mientras el mismo dominio
  siga vinculado a otro servicio activo o suspendido en el mismo servidor.

</details>

<details>
<summary><b>Detección del panel</b></summary>

<br>

El tipo de panel nunca se configura. En la primera llamada el módulo sondea el servidor y recuerda
durante una semana qué panel respondió; **Test Connection** siempre vuelve a sondear.

El puerto solo decide qué panel se prueba **primero**: `8443` y `8880` prueban antes Plesk,
cualquier otro prueba antes cPanel. Una suposición errónea ralentiza la detección, no la rompe.
Durante la detección, el módulo también envía la credencial configurada al endpoint de API del otro
panel en el **mismo** servidor; nunca contacta con ningún otro host.

</details>

<details>
<summary><b>Credenciales</b></summary>

<br>

Ambos paneles reciben su credencial en el campo **Password**, que WiseCP guarda cifrado. El campo
**Access Hash** se guarda como texto plano, por lo que este módulo no lo ofrece y nunca lo lee. En
Plesk el módulo prueba primero la credencial como clave secreta y recurre por su cuenta al usuario y
la contraseña.

</details>

<details>
<summary><b>WiseCP 3</b></summary>

<br>

El paquete de WiseCP 3 (`v3/coremio/`) ofrece el mismo aprovisionamiento en las pantallas propias de
WiseCP 3:

1. **Products / Services → Hosting/Server → Shared Server Settings → Add New Shared Server.** Elija
   `DNAHosting` como **Server Automation Type** y rellene **IP Address**, **Username**, **Password**
   (el token de la API de WHM, la clave secreta de Plesk o la contraseña de Plesk), **Connect with
   SSL** y **Port** (`2087` para cPanel, `8443` para Plesk). El campo **Hostname** de esa pantalla es
   solo una etiqueta; el módulo se conecta a **IP Address**. Pulse **Test Connection**.
2. **Products / Services → Hosting/Server → Web Hosting Packages** → abra el paquete → pestaña
   **Module Settings**. Elija el servidor o el grupo; el módulo muestra el **Detected panel** y la
   lista **Package / Plan**.

Diferencias con el paquete de WiseCP 5:

- WiseCP 3 no tiene un interruptor de verificación de certificados para los módulos de servidor.
  Cuando el **Hostname** del servidor es un nombre de host completo que cubre el certificado del
  panel, el paquete se conecta a él y verifica el certificado; con solo una dirección IP se conecta a
  la IP sin verificación.
- El área de cliente muestra el uso de disco y de ancho de banda y un botón para iniciar sesión en el
  panel; las tarjetas de resumen del servicio de WiseCP 5 no existen en WiseCP 3.
- En Plesk, cambiar el dominio de un servicio activo se rechaza en lugar de dejarse en manos del
  operador.

</details>

<details>
<summary><b>Fuera del alcance</b></summary>

<br>

Herramientas de gestión de correo, bases de datos y archivos, la venta de cuentas de revendedor, la
importación de cuentas existentes a WiseCP y un botón de *iniciar sesión como administrador del
servidor*. El módulo solo guarda una credencial de **revendedor**, nunca root. La importación se ha
dejado fuera a propósito: una suscripción de Plesk importada no llevaría la marca de propiedad de
este módulo, y toda operación posterior sobre ella se rechazaría.

</details>

---

## 📄 Historial de cambios

Los cambios versión a versión están en [CHANGELOG.md](CHANGELOG.md).

---

<div align="center">

**DNA Reseller Hosting** · Módulo de revendedor cPanel y Plesk para WiseCP

[domainnameapi.com](https://www.domainnameapi.com) · [Panel de revendedor](https://dm.domainnameapi.com/hosting)

</div>
