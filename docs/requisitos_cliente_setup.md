# Requisitos de Información del Cliente para Chatbot y Callcenter

## Introducción

Para garantizar el correcto funcionamiento del sistema de chatbot y callcenter, es necesario que el cliente prepare y proporcione la información de las siguientes tablas de su base de datos. Esta información es fundamental para la sincronización y operación del sistema.

## Tablas Requeridas

### 1. Tabla de Citas (`citas`)

**Propósito**: Gestión de citas médicas y su estado de confirmación

**Campos obligatorios**:

-   `IdCita`: Identificador único de la cita
-   `FechaSolicitud`: Fecha en que se solicitó la cita
-   `FeCita`: Fecha programada de la cita
-   `FechaCita`: Hora y fecha específica de la cita
-   `IdMedico`: Identificación del médico asignado
-   `NumeroPaciente`: Número de identificación del paciente
-   `CreadoPor`: Usuario que creó la cita
-   `Entidad`: Entidad de salud o aseguradora
-   `Cancelada`: Estado de cancelación (0: No cancelada, 1: Cancelada)
-   `FechaPideUsuario`: Fecha de solicitud del usuario
-   `Agenda`: Identificador de la agenda médica
-   `FechaCancelacion`: Fecha de cancelación (si aplica)
-   `Confirmada`: Estado de confirmación (0: No confirmada, 1: Confirmada)
-   `FechaConfirmacion`: Fecha y hora de confirmación
-   `MedioConfirmacion`: Medio usado para confirmar ('whatsapp', 'voz')
-   `IdMedioConfirmacion`: ID del mensaje o llamada de confirmación

### 2. Tabla de Agendas (`tblagendas`)

**Propósito**: Configuración de horarios y agendas médicas

**Campos obligatorios**:

-   `RegistroNo`: Identificador único del registro
-   `IdTercero`: Documento del médico
-   `NombreAgenda`: Nombre descriptivo de la agenda

**⚠️ IMPORTANTE - Nomenclatura del Nombre de Agenda**:

El sistema utiliza el nombre de la agenda para filtrar y asignar procedimientos específicos. El cliente **DEBE** seguir estas reglas de nomenclatura:

1. **Agendas para procedimientos nocturnos**: El nombre debe contener la palabra `nocturno`

    - Ejemplo: `"Agenda Nocturna Dr. García"`, `"Turno Nocturno Cardiología"`

2. **Agendas para procedimientos regulares**: El nombre debe contener la palabra `procedimiento`

    - Ejemplo: `"Agenda Procedimientos Dr. López"`, `"Turno Procedimientos Quirúrgicos"`

3. **Agendas para exámenes**: El nombre debe contener la palabra `examen`
    - Ejemplo: `"Agenda Exámenes Laboratorio"`, `"Turno Exámenes Diagnósticos"`

**¿Por qué es importante?**

-   El sistema filtra automáticamente las agendas según el tipo de procedimiento CUPS
-   Los procedimientos de tipo `nocturno` solo se pueden agendar en agendas que contengan "nocturno" en el nombre
-   Los procedimientos de tipo `procedimiento` solo se pueden agendar en agendas que contengan "procedimiento" en el nombre
-   Los procedimientos de tipo `examen` solo se pueden agendar en agendas que contengan "examen" en el nombre

**Recomendación**: Usar nombres descriptivos y consistentes que faciliten la identificación del tipo de agenda.

### 3. Tabla de Configuración de Citas (`citas_conf`)

**Propósito**: Configuración de horarios y disponibilidad médica

**Campos obligatorios**:

-   `IdConfig`: Identificador único de la configuración
-   `IdMedico`: Documento del médico
-   `DuracionCita`: Duración en minutos de cada cita
-   `Activo`: Estado activo de la configuración
-   `IdAgenda`: Identificador de la agenda
-   `SesionesxCita`: Número de sesiones por cita

**Configuración de días laborales**:

-   `Trabaja0` a `Trabaja6`: Días de trabajo (0=domingo, 1=lunes, ..., 6=sábado)

**Horarios de mañana**:

-   `HInicioM0` a `HInicioM6`: Hora de inicio mañana para cada día
-   `HFinalM0` a `HFinalM6`: Hora de fin mañana para cada día

**Horarios de tarde**:

-   `HInicioT0` a `HInicioT6`: Hora de inicio tarde para cada día
-   `HFinalT0` a `HFinalT6`: Hora de fin tarde para cada día

### 4. Tabla de Excepciones de Días (`tblexepciondias`)

**Propósito**: Días especiales o excepciones en el calendario médico

**Campos obligatorios**:

-   `RegistroNo`: Identificador único del registro
-   `IdTercero`: Documento del médico
-   `Fecha`: Fecha de la excepción
-   `JornadaM`: Jornada de mañana habilitada (0/1)
-   `JornadaT`: Jornada de tarde habilitada (0/1)
-   `IdAgenda`: Identificador de la agenda
-   `TipoExcepcion`: Tipo de excepción

### 5. Tabla de Pacientes (`pacientes`)

**Propósito**: Información demográfica y de contacto de los pacientes

**Campos obligatorios**:

-   `NumeroPaciente`: Número único del paciente
-   `TipoID`: Tipo de documento de identidad
-   `IDPaciente`: Número de documento del paciente
-   `Apellido1`, `Apellido2`: Apellidos del paciente
-   `Nombre1`, `Nombre2`: Nombres del paciente
-   `NCompleto`: Nombre completo del paciente
-   `TipoAfiliacion`: Tipo de afiliación a la salud
-   `TipoUsuario`: Tipo de usuario del sistema
-   `FechaNacimiento`: Fecha de nacimiento
-   `SexoPaciente`: Género del paciente
-   `Direccion`: Dirección de residencia
-   `Municipio`: Código del municipio
-   `Telefono`: Número telefónico de contacto
-   `Zona`: Zona geográfica
-   `Ocupacion`: Ocupación del paciente
-   `CorreoE`: Correo electrónico
-   `EntidadPaciente`: Código de la entidad de salud

### 6. Tabla de Procedimientos CUPS (`cups_procedimientos`)

**Propósito**: Catálogo de procedimientos y exámenes médicos

**Campos obligatorios**:

-   `id`: Identificador único del procedimiento
-   `codigo_cups`: Código CUPS del procedimiento (debe ser único)
-   `nombre`: Nombre del procedimiento
-   `descripcion`: Descripción detallada del procedimiento
-   `especialidad_id`: ID de la especialidad médica relacionada
-   `servicio_id`: ID del servicio relacionado
-   `preparacion`: Instrucciones de preparación para el paciente
-   `direccion`: Dirección donde se realiza el procedimiento
-   `video_url`: URL del video instructivo (opcional)
-   `audio_url`: URL del audio instructivo (opcional)
-   `tipo`: Tipo de servicio ENUM('procedimiento', 'examen', 'nocturno') - por defecto 'examen'
-   `horario_especifico_id`: ID del horario específico si aplica (NULL = cualquier horario)
-   `activo`: Estado activo del procedimiento (1: activo, 0: inactivo)
-   `created_at`: Fecha de creación (automático)
-   `updated_at`: Fecha de última actualización (automático)

**⚠️ IMPORTANTE - Relación con Agendas**:

El campo `tipo` determina en qué tipo de agenda se puede agendar el procedimiento:

-   **`nocturno`**: Solo se puede agendar en agendas cuyo nombre contenga la palabra "nocturno"
-   **`procedimiento`**: Solo se puede agendar en agendas cuyo nombre contenga la palabra "procedimiento"
-   **`examen`**: Solo se puede agendar en agendas cuyo nombre contenga la palabra "examen"

Esta validación es automática y es fundamental para el correcto funcionamiento del sistema de agendamiento.

**Características técnicas de la tabla**:

-   **Restricción UNIQUE**: El campo `codigo_cups` debe ser único en toda la tabla
-   **Valor por defecto**: El campo `tipo` tiene valor por defecto 'examen'
-   **Campos opcionales**: `video_url`, `audio_url`, `horario_especifico_id` pueden ser NULL
-   **Timestamps automáticos**: `created_at` y `updated_at` se actualizan automáticamente
-   **Motor de base de datos**: InnoDB con soporte para transacciones
-   **Codificación**: UTF-8 para soporte de caracteres especiales

### 7. Tabla de Asignación Médico-CUP (`cup_medico`)

**Propósito**: Médicos autorizados para realizar procedimientos específicos

**Campos obligatorios**:

-   `id`: Identificador único del registro
-   `cup_id`: ID del procedimiento CUPS (referencia a cups_procedimientos.id)
-   `doctor_documento`: Documento del médico (VARCHAR 30)
-   `doctor_nombre_completo`: Nombre completo del médico (VARCHAR 150)
-   `activo`: Estado activo de la asignación (1: activo, 0: inactivo)
-   `created_at`: Fecha de creación (automático)
-   `updated_at`: Fecha de última actualización (automático)

**Restricciones y características**:

-   **Clave única compuesta**: `(cup_id, doctor_documento)` - un médico no puede estar duplicado para el mismo CUP
-   **Clave foránea**: `cup_id` referencia a `cups_procedimientos(id)` con CASCADE
-   **Valor por defecto**: `activo` tiene valor por defecto 1
-   **Timestamps automáticos**: `created_at` y `updated_at` se actualizan automáticamente

### 8. Tabla de Horarios Específicos (`cups_horarios_especificos`)

**Propósito**: Horarios permitidos para procedimientos específicos

**Campos obligatorios**:

-   `id`: Identificador único del horario
-   `cup_id`: ID del procedimiento CUPS (referencia a cups_procedimientos.id)
-   `hora`: Hora del día (0-23, TINYINT)
-   `activo`: Estado activo del horario (1: activo, 0: inactivo)
-   `created_at`: Fecha de creación (automático)
-   `updated_at`: Fecha de última actualización (automático)

**Restricciones y características**:

-   **Clave única compuesta**: `(cup_id, hora)` - no puede haber duplicados de hora para el mismo CUP
-   **Clave foránea**: `cup_id` referencia a `cups_procedimientos(id)` con CASCADE
-   **Validación de hora**: CHECK constraint asegura que la hora esté entre 0 y 23
-   **Valor por defecto**: `activo` tiene valor por defecto 1
-   **Timestamps automáticos**: `created_at` y `updated_at` se actualizan automáticamente
-   **CASCADE**: Al eliminar un CUP, se eliminan automáticamente sus horarios

### 9. Tabla de Procedimientos por Cita (`pxcita`)

**Propósito**: Procedimientos asociados a cada cita

**Campos obligatorios**:

-   `RegistroNo`: Identificador único del registro
-   `FechaCreado`: Fecha de creación del registro
-   `IdCita`: ID de la cita relacionada
-   `CUPS`: Código CUPS del procedimiento
-   `Cantidad`: Cantidad del procedimiento
-   `VrUnitario`: Valor unitario del procedimiento
-   `IdServicio`: ID del servicio
-   `Facturado`: Estado de facturación
-   `IdPaquete`: ID del paquete (si aplica)

**Notas importantes**:

-   Esta tabla establece la relación muchos a muchos entre citas y procedimientos CUPS
-   Un paciente puede tener múltiples procedimientos en una sola cita
-   El campo `Facturado` controla el estado de facturación del procedimiento
-   `IdPaquete` permite agrupar procedimientos en paquetes de servicios

### 10. Tabla de Entidades (`entidades`)

**Propósito**: Información de entidades de salud y aseguradoras

**Campos obligatorios**:

-   `NoRegistro`: Número de registro único
-   `IDEntidad`: Código de la entidad
-   `NombreEntidad`: Nombre de la entidad
-   `TipoPrecio`: Tipo de precio aplicable
-   `contratoactivo`: Estado del contrato (1: activo, 0: inactivo)

**Notas importantes**:

-   Esta tabla define las entidades de salud, EPS, aseguradoras y otros proveedores
-   El campo `TipoPrecio` determina qué tarifa aplicar para los procedimientos
-   `contratoactivo` controla si la entidad tiene contrato vigente
-   Los precios de procedimientos se calculan según el tipo de precio de la entidad

### 11. Tabla de Códigos SOAT (`codigossoat`)

**Propósito**: Tarifas y códigos para servicios SOAT

**Campos obligatorios**:

-   `CodigoCUPS`: Código CUPS del procedimiento
-   `CodigoISS`: Código ISS correspondiente
-   `Tarifa01` a `Tarifa40`: Tarifas del 01 al 40

**Notas importantes**:

-   Esta tabla contiene las tarifas oficiales para procedimientos SOAT
-   Las tarifas del 01 al 40 corresponden a diferentes tipos de cobertura o categorías
-   El sistema usa esta información para calcular precios según el tipo de cliente
-   Los códigos ISS son códigos oficiales del sistema de salud colombiano
-   Es fundamental mantener actualizadas las tarifas para el correcto funcionamiento del sistema de precios

## Requisitos Adicionales

### Configuración de Base de Datos

1. **Usuario de aplicación**: Crear usuario `appuser` con permisos específicos
2. **Permisos**:
    - Acceso completo a `datosipsndx.*`
    - Acceso de solo lectura a `contabilidaddndx.*`
3. **Índices**: Asegurar que existan índices para optimizar consultas frecuentes

### Formato de Datos

1. **Fechas**: Utilizar formato `YYYY-MM-DD HH:MM:SS`
2. **Estados**: Usar valores binarios (0/1) para campos booleanos
3. **Códigos**: Mantener consistencia en códigos CUPS e identificadores
4. **Caracteres especiales**: Usar codificación UTF-8 para nombres y descripciones

### Mantenimiento

1. **Actualizaciones**: Mantener actualizada la información de procedimientos y médicos
2. **Validaciones**: Implementar validaciones para evitar datos duplicados o inconsistentes
3. **Backups**: Realizar respaldos regulares de las tablas críticas

## ⚠️ PUNTOS CRÍTICOS DE ATENCIÓN

### 1. Nomenclatura de Agendas (OBLIGATORIO)

**Este es el punto más crítico del sistema**. El cliente **DEBE** seguir estrictamente las reglas de nomenclatura para los nombres de las agendas:

-   **Agendas nocturnas**: Nombre debe contener `nocturno`
-   **Agendas de procedimientos**: Nombre debe contener `procedimiento`
-   **Agendas de exámenes**: Nombre debe contener `examen`

**Consecuencias de no seguir esta regla**:

-   Los procedimientos no se podrán agendar correctamente
-   El sistema fallará al buscar agendas disponibles
-   Los pacientes no podrán reservar citas
-   El chatbot y callcenter no funcionarán correctamente

### 2. Consistencia de Datos

-   Verificar que todos los campos obligatorios estén presentes
-   Asegurar que los códigos CUPS sean únicos y válidos
-   Validar que las fechas estén en formato correcto
-   Confirmar que los estados booleanos usen 0/1

### 3. Permisos de Base de Datos

-   Crear usuario `appuser` con permisos específicos
-   Configurar acceso a las bases de datos correctas
-   Verificar que los índices estén creados para optimizar consultas

## Contacto

Para cualquier consulta sobre la preparación de esta información o asistencia técnica, contactar al equipo de implementación.

---

**Nota**: La correcta preparación de esta información es fundamental para el funcionamiento óptimo del sistema de chatbot y callcenter. Se recomienda revisar y validar todos los datos antes de la implementación.

**⚠️ ADVERTENCIA FINAL**: La nomenclatura incorrecta de las agendas es la causa más común de fallos en el sistema. Dedique tiempo extra a revisar y validar esta configuración.
