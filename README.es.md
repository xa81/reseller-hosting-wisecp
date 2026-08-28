<h1 align="center">DNA Reseller Hosting</h1>

<p align="center">
  <strong>Vende hosting compartido sobre cPanel/WHM y Plesk con un único módulo de servidor de WiseCP.</strong><br>
  Un módulo, dos paneles: nunca eliges el tipo de panel, el módulo lo descubre solo.
</p>

<p align="center">
  <img alt="WiseCP" src="https://img.shields.io/badge/WiseCP-self--hosted-4A90D9?style=flat-square">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-7.4%20%E2%80%93%208.4-777BB4?style=flat-square&logo=php&logoColor=white">
  <img alt="cPanel/WHM" src="https://img.shields.io/badge/cPanel%2FWHM-compatible-FF6C2C?style=flat-square">
  <img alt="Plesk" src="https://img.shields.io/badge/Plesk-compatible-53BCE6?style=flat-square">
  <img alt="Licencia" src="https://img.shields.io/badge/licencia-propietaria-lightgrey?style=flat-square">
</p>

<p align="center">
  <a href="README.md">Türkçe</a>
  · <a href="README.en.md">English</a>
  · <a href="README.de.md">Deutsch</a>
  · <a href="README.ru.md">Русский</a>
  · <a href="README.az.md">Azərbaycan</a>
  · <a href="README.ar.md">العربية</a>
  · <strong>Español</strong>
  · <a href="README.fr.md">Français</a>
</p>

---

## Índice

- [Descripción general](#descripción-general)
- [Matriz de funciones](#matriz-de-funciones)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Configuración](#configuración)
  - [Paso 1 — Añadir un servidor](#paso-1--añadir-un-servidor)
  - [Paso 2 — Grupos de servidores (opcional)](#paso-2--grupos-de-servidores-opcional)
  - [Paso 3 — Definir el producto](#paso-3--definir-el-producto)
- [Solución de problemas](#solución-de-problemas)
- [Registros](#registros)
- [Historial de cambios](#historial-de-cambios)
- [Licencia](#licencia)

---

## Descripción general

El módulo gobierna las dos familias de paneles desde un único registro de servidor. Introduces la IP, el
usuario de revendedor y una credencial; el módulo consulta el servidor de verdad —una llamada real a la
API, no una suposición— y recuerda qué panel respondió.

| | |
|---|---|
| **Tipo de módulo** | Módulo de servidor (Servers) de WiseCP |
| **Nombre de la carpeta** | `DNAHosting` |
| **Versión** | 1.0.0 |
| **Paneles compatibles** | cPanel/WHM, Plesk |
| **PHP** | 7.4 – 8.4 |
| **Idiomas de la interfaz** | turco, inglés (`lang/tr.php`, `lang/en.php`) |

---

## Matriz de funciones

| Operación | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Prueba de conexión y detección automática del panel | ✔ | ✔ |
| Creación de cuenta | ✔ | ✔ |
| Suspender / reactivar | ✔ | ✔ |
| Cancelación | ✔ | ✔ (con verificación de propiedad) |
| Cambio de contraseña | ✔ | ✔ |
| Cambio de paquete / plan | ✔ | ✔ |
| Uso de disco y tráfico (en la página del servicio del cliente) | ✔ | ✔ |
| Acceso al panel con un clic — área de cliente | ✔ | ✔ |
| Acceso al panel con un clic — área de administración | ✔ | ✔ |

---

## Requisitos

- Una instalación de **WiseCP** en tu propio servidor a la que tengas acceso de administrador
- PHP con las extensiones **cURL** y **SimpleXML** habilitadas (presentes en casi cualquier instalación
  por defecto)
- O bien una **cuenta de revendedor de cPanel/WHM** (con un token de la API de WHM), o bien una **cuenta
  de revendedor de Plesk** (con una clave de API o directamente con la contraseña del panel)
- Acceso de red saliente desde el servidor de WiseCP hacia el servidor del panel, en el puerto de la API
  del panel

No se crea ninguna tabla en la base de datos, no se instala nada con Composer, no hay paso de compilación.

---

## Instalación

Copia la carpeta del módulo en tu instalación de WiseCP:

```
coremio/
└── modules/
    └── Servers/
        └── DNAHosting/     ← la carpeta entera va aquí
```

Esa es toda la instalación. Después no hay nada que ejecutar: ni migraciones, ni precalentado de caché,
ni un paso de activación aparte. El módulo aparece en la lista la próxima vez que abras la pantalla de
añadir servidor.

---

## Configuración

> Las rutas de menú siguientes corresponden a la interfaz de WiseCP en inglés.

### Paso 1 — Añadir un servidor

**Products / Services → Hosting/Server → Shared Server Settings → `Add New Shared Server`**

Rellena la sección **Server Automation Information** del formulario:

| Campo | Qué introducir |
|---|---|
| **Server Automation Type** | `DNAHosting`: es el nombre de la carpeta y aparece tal cual en la lista |
| **IP Address** | La dirección real del servidor del panel; el módulo se conecta ahí |
| **Username** | Tu usuario de revendedor en ese panel |
| **Password** | **cPanel:** el token de la API de WHM. **Plesk:** la clave de API o la contraseña del panel del revendedor |
| **Connect with SSL** | Márcalo |
| **Port** | `2087` para cPanel, `8443` para Plesk |

El campo **Hostname** de la parte superior del formulario es solo una etiqueta para ti: el módulo usa el
campo **IP Address**, no ese, para conectarse. En la pantalla de listado tus servidores aparecen con esa
etiqueta.

### Paso 2 — Grupos de servidores (opcional)

Si tienes más de un servidor, puedes crear un grupo en **Shared Server Settings → `Server Groups`** y
vincular el producto al grupo en lugar de a un servidor concreto. La pantalla de edición del grupo ofrece
dos tipos de distribución:

- **Añadir siempre al servidor menos ocupado.**
- **Llenar un servidor por completo y luego pasar al menos ocupado.**

Los servidores se mueven entre las listas **Unassigned → Assigned** con `Add` / `Remove`.

> [!IMPORTANT]
> **Mantén cada grupo homogéneo por panel.** La lista de paquetes del formulario del producto se obtiene
> de **un solo** servidor: el seleccionado en ese momento. Si un grupo contiene un servidor cPanel y otro
> Plesk, el nombre de paquete elegido puede no tener equivalente en el otro panel y el pedido que caiga
> en ese servidor fallará con «paquete no encontrado».

### Paso 3 — Definir el producto

**Products / Services → Hosting/Server → Web Hosting Packages** → abre el paquete → pestaña **Module
Settings**.

En **Server Selection**, elige **Single Server** o **Server Group** y marca tu servidor (o grupo)
DNAHosting. En cuanto haces la selección, el módulo dibuja sus propios campos:

| Campo | Significado |
|---|---|
| **Detected panel** | El panel que el módulo ha encontrado realmente en ese servidor, por ejemplo `cPanel / WHM`. Aquí ves que la detección funciona; si algo falla, en esta línea aparece el texto del error. |
| **Package / Plan** | La lista de paquetes obtenida en vivo de ese servidor |
| **Automatic Setup** | Activado, el pedido se aprovisiona automáticamente; desactivado, requiere aprobación del administrador |

La lista de paquetes depende del panel:

- **cPanel:** cada paquete de la salida de `listpkgs` del servidor. Si tus paquetes llevan el prefijo de
  revendedor habitual (por ejemplo `bakcay328_paket1`), el módulo resuelve el prefijo por su cuenta.
- **Plesk:** cada plan de servicio definido en el servidor.

Elige el paquete, completa el resto del formulario como siempre y guarda. El producto ya se puede vender:
al realizarse un pedido, todo el flujo de creación de cuenta se ejecuta contra el servidor que has
configurado.

---

## Solución de problemas

| Síntoma | Causa | Solución |
|---|---|---|
| Una llamada (normalmente la prueba de conexión) falla con `HTTP 403`, o el texto del error menciona un envoltorio `cpanelresult` | La cuenta de revendedor asociada al token no tiene privilegio a nivel de WHM para esa función; WHM respondió con la API de **usuario** de cPanel en lugar de WHM API 1 | En WHM abre **Resellers → Edit Reseller's ACL List** y concede al revendedor los privilegios que usa el módulo: listado y resumen de cuentas, creación de cuenta, suspensión, cancelación, cambio de contraseña, mejora de paquete, listado de paquetes, lectura de tráfico y creación de sesión. Después regenera el token **con la sesión iniciada como ese revendedor** en **WHM → Development → Manage API Tokens**: un token generado desde la interfaz de cPanel no da acceso a WHM |
| `Plesk (11003)` | La clave de API se generó para una dirección distinta de la IP desde la que conecta WiseCP | Genera una clave nueva en el servidor Plesk para la IP correcta, o pon la contraseña del panel en el campo Password |
| `Plesk (1014)` | Plesk rechazó el cuerpo de la petición: falta un elemento o está en el lugar equivocado para la versión de XML-API que habla ese servidor | Comprueba que usas la versión actual del módulo; el log del módulo muestra a qué elemento exacto puso pegas Plesk |
| En el formulario del producto aparece un texto de error en vez de la lista de paquetes | Falló la detección o la llamada de paquetes; el motivo está escrito en esa misma línea | Aplica una de las filas anteriores según el error concreto del texto |

Cualquier otro error HTTP llega con un resumen en texto plano extraído del cuerpo de respuesta del panel;
un código de estado a secas nunca es la historia completa. Consulta el log del módulo para ver la petición
y la respuesta completas.

---

## Registros

**Tools → Logs → Module Logs**

Cada petición que envía el módulo y cada respuesta que recibe se escriben aquí, etiquetadas con el nombre
de la operación (por ejemplo `createacct`, `webspace.add`). Solo se guardan mientras la función **Module
Logs** está activada; ese interruptor está en la parte superior de la misma página.

> [!NOTE]
> El token de API o la contraseña del servidor, las contraseñas de cuenta que el módulo genera o cambia y
> los tokens de sesión SSO —tanto en la petición como en la respuesta— se enmascaran con `***` antes de
> escribirse.

---

## Historial de cambios

Consulta [CHANGELOG.md](CHANGELOG.md) para ver los cambios versión a versión.

---

## Licencia

Propietaria. Todos los derechos reservados.
