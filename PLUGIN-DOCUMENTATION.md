# 📚 Documentación del Plugin - Comentarios Free

**Versión:** 1.1.0  
**Autor:** Equipo Free Walking Tour  
**Última Actualización:** 15 de Noviembre de 2025  
**Estado:** ✅ Listo para Producción

---

## 📋 Índice

1. [Descripción General](#descripción-general)
2. [Características Principales](#características-principales)
3. [Estructura del Plugin](#estructura-del-plugin)
4. [Funcionalidades Detalladas](#funcionalidades-detalladas)
5. [Sistema de Gestión](#sistema-de-gestión)
6. [Base de Datos](#base-de-datos)
7. [APIs y Endpoints](#apis-y-endpoints)
8. [Seguridad](#seguridad)
9. [Compatibilidad](#compatibilidad)
10. [Guía de Uso](#guía-de-uso)

---

## 🎯 Descripción General

Sistema completo de gestión de comentarios y reseñas para WordPress, diseñado específicamente para tours y experiencias turísticas. Permite a los usuarios dejar reseñas con calificación de estrellas, fotos, información de viaje y más.

### Características Clave:
- ✅ Sistema de calificación con estrellas (1-5)
- ✅ Subida múltiple de imágenes (hasta 5 fotos por reseña)
- ✅ Galería de imágenes con navegación
- ✅ Respuestas de administrador inline
- ✅ Filtros por calificación e idioma
- ✅ Panel de administración completo
- ✅ Sistema de edición con límites
- ✅ Integración con WPML (multiidioma)
- ✅ Rich Snippets (Schema.org)
- ✅ Responsive design

---

## 🌟 Características Principales

### 1. Sistema de Reseñas

#### Campos de Información:
- **Calificación** (1-5 estrellas) - Obligatorio
- **Título** - Obligatorio, máx. 100 caracteres
- **Contenido** - Obligatorio, máx. 2000 caracteres
- **Nombre del autor** - Obligatorio
- **Email del autor** - Obligatorio
- **País** - Opcional, con autocompletado
- **Idioma** - ES/EN, por defecto ES
- **Compañía de viaje** - Solo/Pareja/Familia/Amigos
- **Imágenes** - Hasta 5 fotos, máx. 5MB c/u

#### Validaciones:
- Formatos de imagen permitidos: JPG, PNG, GIF, WebP
- Tamaño máximo por imagen: 5MB
- Límite de imágenes: 5 por comentario
- Validación de email
- Prevención de duplicados

### 2. Galería de Imágenes

#### Características:
- **Lightbox Avanzado:**
  - Navegación con flechas ‹ ›
  - Navegación con teclado (← → ESC)
  - Contador de imágenes (ej: "2 / 5")
  - Transiciones suaves
  - Botón cerrar (×)
  - Cierre con click en fondo oscuro
  - Efectos hover animados

#### Gestión de Imágenes:
- Visualización en miniatura (80x80px)
- Vista completa en lightbox
- Eliminación individual
- Nombres de archivo únicos con timestamp
- Almacenamiento en `/wp-content/uploads/comentarios-free/`

### 3. Sistema de Autenticación

#### Modos de Login:
- **Usuarios Registrados:**
  - Login automático con datos de WordPress
  - Perfil vinculado a comentarios
  - Edición de reseñas propias
  
- **Usuarios No Registrados:**
  - Flujo de dos pasos
  - Integración con plugin LoginFree
  - Registro opcional con Google

### 4. Filtros y Búsqueda

#### Filtros Disponibles:
- **Por Calificación:** 1-5 estrellas o "Todos"
- **Por Idioma:** ES/EN o "Todos"
- **Filtrado Local:** Sin recarga de página
- **Estadísticas en Tiempo Real:** Muestra cantidad filtrada

### 5. Panel de Administración

#### Dashboard de Administrador:
- **Estadísticas Globales:**
  - Total de comentarios
  - Promedio de calificación
  - Comentarios pendientes
  - Comentarios con respuesta
  
- **Tabla de Comentarios:**
  - Vista completa de todas las reseñas
  - Filtros por estado y calificación
  - Acciones rápidas (editar/eliminar/responder)
  - Información del usuario
  - Fecha de creación
  - Contador de ediciones

#### Panel de Usuario Suscriptor:
- Visualización de comentarios propios
- Edición con límite de 3 modificaciones
- Estadísticas personales
- Gestión de imágenes

### 6. Sistema de Edición

#### Límites de Edición:
- **Usuarios:** 1 edición de contenido (texto)
- **Solo Fotos:** Ediciones ilimitadas
- **Administradores:** Sin límites
- **Contador Visible:** Muestra si ya editó el contenido

#### Funcionalidades de Edición:
- Modificación de todos los campos
- Agregar/eliminar imágenes
- Sistema de marcado para eliminación
- Validación de cambios
- Mensajes de confirmación con SweetAlert2

### 7. Respuestas del Administrador

#### Características:
- Respuesta inline en cada comentario
- Badge distintivo "👨‍💼 Respuesta"
- Botón "Leer más" si excede 237 caracteres
- Edición y eliminación de respuestas
- Notificación visual al usuario

### 8. Integración WPML

#### Soporte Multiidioma:
- Detección automática de idioma activo
- Comentarios vinculados al post original
- Filtros por idioma
- Sincronización entre traducciones

---

## 📁 Estructura del Plugin

```
comentariosfree/
├── comentarios-free.php           # Archivo principal del plugin
├── assets/
│   ├── css/
│   │   ├── admin-dashboard.css    # Estilos panel admin (1791 líneas)
│   │   ├── admin.css              # Estilos admin general
│   │   └── frontend.css           # Estilos frontend (1848 líneas)
│   └── js/
│       ├── admin-dashboard.js     # JS panel admin (1853 líneas)
│       ├── admin.js               # JS admin general
│       ├── frontend.js            # JS frontend
│       ├── frontend-standalone.js # JS standalone
│       └── user-panel.js          # JS panel usuario
├── includes/
│   ├── class-admin.php            # Configuración admin
│   ├── class-admin-dashboard.php  # Dashboard completo
│   ├── class-ajax.php             # Endpoints AJAX (1808 líneas)
│   ├── class-database.php         # Operaciones BD (714 líneas)
│   ├── class-frontend-twostep.php # Frontend 2 pasos (2313 líneas)
│   ├── class-rich-snippets.php    # Schema.org markup
│   ├── class-user-panel.php       # Panel usuario
│   └── countries-data.php         # Datos de países
└── languages/
    └── comentarios-free.pot       # Archivo de traducción
```

---

## 🔧 Funcionalidades Detalladas

### Sistema de Comentarios

#### 1. Envío de Comentario (`submit_comment`)

**Validaciones:**
- Verificación de nonce
- Validación de campos obligatorios
- Prevención de duplicados
- Detección de usuario logueado
- Sanitización de datos

**Proceso:**
1. Validar datos del formulario
2. Insertar comentario en BD
3. Procesar imágenes si existen
4. Guardar relación imagen-comentario
5. Responder con éxito/error

#### 2. Edición de Comentario (`edit_comment`)

**Características:**
- Verificación de propiedad
- Sistema de conteo de ediciones
- Solo incrementa si modifica texto
- Permite agregar fotos sin límite
- Eliminación de imágenes con marcado

**Validaciones:**
- Usuario debe ser propietario
- Verificar límite de ediciones
- Nonce válido
- Campos requeridos completos

#### 3. Eliminación de Comentario (`delete_comment`)

**Proceso:**
1. Verificar permisos (propietario o admin)
2. Obtener imágenes asociadas
3. Eliminar archivos físicos
4. Eliminar registros de BD
5. Eliminar comentario principal

### Sistema de Imágenes

#### Subida de Imágenes (`handle_image_uploads`)

**Validaciones:**
- Tipo MIME permitido
- Extensión válida
- Tamaño máximo 5MB
- Límite de 5 imágenes
- Archivo temporal existe
- Permisos de escritura

**Proceso:**
1. Crear directorio si no existe
2. Generar nombre único: `{comment_id}_{uniqid}_{timestamp}.{ext}`
3. Mover archivo a destino
4. Verificar archivo creado
5. Insertar registro en BD
6. Retornar array de URLs

**Manejo de Errores:**
```php
// Errores específicos registrados:
- "Archivo muy grande (XMB, máximo 5MB)"
- "Formato no permitido (usar: jpg, png, gif, webp)"
- "Tipo de archivo no válido"
- "Error en archivo temporal"
- "Error de permisos en servidor"
- "Error al guardar en base de datos"
```

#### Eliminación de Imágenes (`delete_image_by_id`)

**Proceso:**
1. Obtener información de la imagen
2. Verificar archivo existe
3. Eliminar archivo físico con `unlink()`
4. Eliminar registro de BD
5. Confirmar operación

### Sistema de Respuestas

#### Respuesta del Administrador (`admin_reply`)

**Validaciones:**
- Usuario debe ser administrador
- Nonce válido
- Contenido no vacío

**Funcionalidades:**
- Guardar respuesta en campo `admin_response`
- Mostrar badge distintivo
- Texto truncado con "Leer más"
- Edición y eliminación de respuesta

### Filtros

#### Filtrado Local (JavaScript)

```javascript
// Filtros disponibles:
- rating: 1|2|3|4|5|all
- language: es|en|all

// Actualiza en tiempo real:
- Oculta/muestra comentarios
- Actualiza contador
- Sin recarga de página
```

#### Filtrado AJAX (`filter_comments`)

**Uso:** Para cargas más complejas
**Proceso:**
1. Recibir filtros vía POST
2. Construir query con `get_comments()`
3. Generar HTML de comentarios
4. Retornar HTML renderizado

---

## 🗄️ Base de Datos

### Tabla Principal: `wp_comentarios_free`

```sql
CREATE TABLE wp_comentarios_free (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT(20) UNSIGNED NOT NULL,
    user_id BIGINT(20) UNSIGNED NULL,
    author_name VARCHAR(255) NOT NULL,
    author_email VARCHAR(255) NOT NULL,
    rating INT(1) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    country VARCHAR(100) DEFAULT '',
    language VARCHAR(10) NOT NULL DEFAULT 'es',
    travel_companion VARCHAR(50) DEFAULT 'solo',
    status VARCHAR(20) DEFAULT 'approved',
    admin_response TEXT NULL,
    edit_count INT(11) DEFAULT 0,
    date_created DATETIME NOT NULL,
    date_modified DATETIME NULL,
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_date_created (date_created),
    INDEX idx_rating (rating)
);
```

### Tabla de Imágenes: `wp_comentarios_images`

```sql
CREATE TABLE wp_comentarios_images (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comment_id BIGINT(20) UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    file_url TEXT NOT NULL,
    file_size BIGINT(20) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    date_uploaded DATETIME NOT NULL,
    FOREIGN KEY (comment_id) REFERENCES wp_comentarios_free(id) ON DELETE CASCADE,
    INDEX idx_comment_id (comment_id)
);
```

### Operaciones de Base de Datos

#### Método: `insert_comment()`
```php
// Inserta nuevo comentario
// Retorna: ID del comentario insertado
```

#### Método: `update_comment()`
```php
// Actualiza comentario existente
// Incrementa edit_count automáticamente
// Actualiza date_modified
```

#### Método: `get_comment()`
```php
// Obtiene comentario con imágenes
// Joins con tabla de imágenes
// Retorna: Objeto con todas las propiedades
```

#### Método: `get_comments()`
```php
// Lista comentarios con filtros
// Soporta: pagination, status, rating, language
// Retorna: Array de objetos
```

#### Método: `delete_comment()`
```php
// Elimina comentario
// Cascada automática elimina imágenes
```

---

## 🔌 APIs y Endpoints

### Endpoints AJAX

Todos los endpoints requieren nonce para seguridad.

#### 1. `comentarios_submit`
**Método:** POST  
**Acceso:** Usuarios logueados y no logueados  
**Datos:**
```javascript
{
    action: 'comentarios_submit',
    post_id: int,
    rating: int (1-5),
    title: string,
    content: string,
    author_name: string,
    author_email: string,
    country: string,
    language: string (es|en),
    travel_companion: string,
    images[]: File[] // Opcional
}
```

#### 2. `comentarios_edit`
**Método:** POST  
**Acceso:** Propietario del comentario  
**Datos:**
```javascript
{
    action: 'comentarios_edit',
    comment_id: int,
    rating: int,
    title: string,
    content: string,
    country: string,
    language: string,
    travel_companion: string,
    new_images[]: File[], // Opcional
    delete_images: string // JSON array de IDs
}
```

#### 3. `comentarios_delete`
**Método:** POST  
**Acceso:** Propietario o administrador  
**Datos:**
```javascript
{
    action: 'comentarios_delete',
    comment_id: int
}
```

#### 4. `comentarios_get_comment`
**Método:** POST  
**Acceso:** Propietario o administrador  
**Datos:**
```javascript
{
    action: 'comentarios_get_comment',
    comment_id: int
}
```
**Respuesta:**
```javascript
{
    success: true,
    data: {
        comment: {
            id, post_id, rating, title, content,
            author_name, author_email, country,
            language, travel_companion, status,
            admin_response, edit_count, date_created
        },
        images: [
            {id, file_url, original_name, file_size}
        ]
    }
}
```

#### 5. `comentarios_admin_edit`
**Método:** POST  
**Acceso:** Solo administradores  
**Datos:** Igual que `comentarios_edit`

#### 6. `comentarios_admin_reply`
**Método:** POST  
**Acceso:** Solo administradores  
**Datos:**
```javascript
{
    action: 'comentarios_admin_reply',
    comment_id: int,
    reply_content: string
}
```

#### 7. `comentarios_delete_image`
**Método:** POST  
**Acceso:** Propietario o administrador  
**Datos:**
```javascript
{
    action: 'comentarios_delete_image',
    image_id: int,
    comment_id: int
}
```

#### 8. `comentarios_filter`
**Método:** POST  
**Acceso:** Público  
**Datos:**
```javascript
{
    action: 'comentarios_filter',
    post_id: int,
    rating_filter: int|'all',
    language_filter: string|'all'
}
```

---

## 🔒 Seguridad

### Medidas de Seguridad Implementadas

#### 1. Verificación de Nonce
```php
// Todos los endpoints verifican nonce
wp_verify_nonce($_POST['comentarios_nonce'], 'comentarios_free_nonce')
```

#### 2. Sanitización de Datos
```php
// Entradas sanitizadas
$rating = absint($_POST['rating']);
$title = sanitize_text_field($_POST['title']);
$content = sanitize_textarea_field($_POST['content']);
$email = sanitize_email($_POST['author_email']);
```

#### 3. Validación de Permisos
```php
// Verificación de propiedad
$comment->user_id === get_current_user_id()

// Verificación de admin
current_user_can('manage_options')
```

#### 4. Prevención de SQL Injection
```php
// Uso de $wpdb->prepare()
$wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
```

#### 5. Validación de Archivos
```php
// Tipos MIME permitidos
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

// Extensiones permitidas
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Tamaño máximo
$max_file_size = 5 * 1024 * 1024; // 5MB
```

#### 6. Escape de Salidas
```php
// HTML escapado
esc_html($text)
esc_attr($attribute)
esc_url($url)
```

#### 7. Prevención de Path Traversal
```php
// Nombres de archivo seguros
$filename = $comment_id . '_' . uniqid() . '_' . time() . '.' . $extension;
```

---

## 🔄 Compatibilidad

### WordPress
- **Versión Mínima:** 5.0
- **Versión Probada:** 6.4
- **PHP Mínimo:** 7.4
- **MySQL Mínimo:** 5.6

### Plugins Compatibles

#### WPML (WordPress Multilingual)
- Detección automática de idioma
- Sincronización de comentarios
- Filtros por idioma

#### LoginFree (Plugin Propio)
- Integración de registro
- Login con Google
- Modal de autenticación

### Navegadores Soportados
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Opera 76+

### Responsive Design
- 📱 Mobile: 320px - 767px
- 📱 Tablet: 768px - 1024px
- 💻 Desktop: 1025px+

---

## 📖 Guía de Uso

### Para Administradores

#### Acceso al Dashboard
1. Ir a `Dashboard → Comentarios Free`
2. Ver estadísticas globales
3. Gestionar todos los comentarios

#### Responder a un Comentario
1. Click en botón "💬 Responder"
2. Escribir respuesta
3. Guardar
4. La respuesta aparece con badge distintivo

#### Editar Comentario como Admin
1. Click en botón "✏️ Editar"
2. Modificar cualquier campo
3. Agregar/eliminar imágenes
4. Guardar cambios
5. Sin límite de ediciones

#### Eliminar Comentario
1. Click en botón "🗑️ Eliminar"
2. Confirmar acción
3. Se eliminan comentario e imágenes

### Para Usuarios

#### Dejar una Reseña
1. Hacer click en "Escribir una Reseña"
2. **Si no está logueado:**
   - Iniciar sesión o registrarse
   - Completar datos personales
3. **Si está logueado:**
   - Formulario directo
4. Completar formulario:
   - Seleccionar estrellas (obligatorio)
   - Título (obligatorio)
   - Contenido (obligatorio)
   - País (opcional)
   - Idioma (ES/EN)
   - Compañía de viaje
   - Fotos (opcional, hasta 5)
5. Click en "Publicar"

#### Editar Reseña Propia
1. Ir a `Dashboard → Panel de Usuario`
2. Ver reseñas propias
3. Click en "✏️ Editar"
4. Modificar campos
5. **Límite:** 1 edición de contenido (texto)
6. **Fotos:** Sin límite de ediciones
7. Guardar cambios

#### Eliminar Imágenes
1. Abrir modal de edición
2. Click en × sobre la imagen
3. Imagen se marca (opacidad 0.3)
4. Guardar formulario
5. Imagen eliminada permanentemente

### Galería de Imágenes

#### Visualizar Imágenes
1. Click en cualquier imagen miniatura
2. Se abre lightbox con imagen completa

#### Navegación en Galería
- **Flechas ‹ ›:** Imagen anterior/siguiente
- **Teclado ←  →:** Navegación
- **ESC:** Cerrar galería
- **Click en fondo:** Cerrar
- **Botón ×:** Cerrar

### Filtros

#### Filtrar por Calificación
1. Seleccionar estrellas en dropdown
2. Comentarios se filtran automáticamente
3. Contador actualizado

#### Filtrar por Idioma
1. Seleccionar ES/EN en dropdown
2. Comentarios se filtran automáticamente
3. Contador actualizado

---

## 📊 Estadísticas y Métricas

### Dashboard de Administrador

**Tarjetas de Estadísticas:**
- 📊 Total de Comentarios
- ⭐ Promedio de Calificación
- ⏳ Comentarios Pendientes
- 💬 Comentarios con Respuesta

**Tabla de Gestión:**
- Columna: Usuario
- Columna: Post
- Columna: Calificación
- Columna: Título
- Columna: Estado
- Columna: Fecha
- Columna: Ediciones
- Columna: Acciones

### Panel de Usuario

**Estadísticas Personales:**
- Total de reseñas
- Promedio de calificación
- Estado de edición de contenido
- Última reseña

---

## 🎨 Personalización

### Estilos CSS

#### Variables CSS Principales
```css
:root {
    --cf-primary-color: #007cba;
    --cf-secondary-color: #f0f0f0;
    --cf-success-color: #28a745;
    --cf-warning-color: #ffc107;
    --cf-danger-color: #dc3545;
}
```

#### Clases Importantes
- `.cf-comment-item` - Contenedor de comentario
- `.cf-rating-stars` - Estrellas de calificación
- `.cf-comment-images` - Contenedor de imágenes
- `.cf-admin-response` - Respuesta del admin
- `.cf-lightbox` - Galería de imágenes

### JavaScript Events

#### Eventos Personalizados
```javascript
// Comentario enviado
$(document).trigger('comentarios:submitted', [commentId]);

// Comentario editado
$(document).trigger('comentarios:edited', [commentId]);

// Comentario eliminado
$(document).trigger('comentarios:deleted', [commentId]);
```

---

## 🐛 Troubleshooting

### Problemas Comunes

#### 1. Imágenes no se suben
**Posibles causas:**
- Permisos del directorio `/wp-content/uploads/`
- Tamaño de archivo > 5MB
- Formato no permitido
- Límite PHP `upload_max_filesize`

**Solución:**
```php
// Verificar en php.ini:
upload_max_filesize = 10M
post_max_size = 10M
```

#### 2. Error de nonce
**Causa:** Sesión expirada
**Solución:** Recargar página

#### 3. Límite de ediciones alcanzado
**Causa:** Ya editó el contenido 1 vez
**Solución:** 
- Solo puede agregar/quitar fotos
- Contactar administrador para modificar texto

#### 4. Filtros no funcionan
**Causa:** JavaScript desactivado o conflicto
**Solución:**
- Verificar consola del navegador
- Desactivar otros plugins temporalmente

---

## 📝 Changelog

### Versión 1.1.0 (15/11/2025)
- ✨ Nuevo: Galería de imágenes con navegación
- ✨ Nuevo: Sistema de marcado para eliminación de imágenes
- ✨ Nuevo: Respuestas del administrador con "Leer más"
- ✨ Nuevo: Validación de imágenes en frontend
- 🔧 Mejora: Límite de tamaño de imágenes aumentado a 5MB
- 🔧 Mejora: Sistema de edición mejorado (solo cuenta cambios de texto)
- 🔧 Mejora: Mensajes con SweetAlert2
- 🔧 Mejora: Logs de debug detallados
- 🐛 Fix: Corrección de conflictos entre modales
- 🐛 Fix: País no se guardaba correctamente
- 🗑️ Limpieza: Eliminados todos los console.log
- 🗑️ Limpieza: Eliminados error_log de debug

### Versión 1.0.0 (Inicial)
- 🎉 Lanzamiento inicial
- ✅ Sistema básico de comentarios
- ✅ Calificación con estrellas
- ✅ Subida de imágenes
- ✅ Panel de administración
- ✅ Integración WPML

---

## 🤝 Soporte

### Contacto
- **Email:** soporte@freewalkingtour.com
- **Documentación:** Este archivo
- **Desarrollo:** Equipo Interno

### Recursos
- Archivo principal: `comentarios-free.php`
- Documentación PHP: Comentarios inline en cada clase
- Documentación JS: Comentarios en archivos JS

---

## 📄 Licencia

Propiedad de Free Walking Tour. Uso interno exclusivo.

---

**Última Actualización de Documentación:** 15 de Noviembre de 2025  
**Estado del Plugin:** ✅ Producción  
**Versión Documentada:** 1.1.0
