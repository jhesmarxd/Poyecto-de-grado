# CAPÍTULO III. INGENIERÍA DEL PROYECTO
## Estructura General y Planificación del Proyecto de Grado

**Proyecto:** Sistema Web de Gestión y Reserva de Canchas de Wally con Asistente Virtual Inteligente (NLP)  
**Postulante:** Jhesmar Fuentes  
**Marco Metodológico:** Marco Ágil Scrum (4 Sprints de desarrollo incremental)

---

### ÍNDICE DE CONTENIDOS DEL CAPÍTULO III

* **3.1. Requerimientos generales del sistema**
  * 3.1.1. Requerimientos funcionales (RF)
  * 3.1.2. Requerimientos no funcionales (RNF)
  * 3.1.3. Identificación y descripción de los actores principales (Administrador, Recepcionista y Cliente)
  * 3.1.4. Diagrama general de casos de uso del sistema
  * 3.1.5. Diagrama general de clases del dominio
  * 3.1.6. Diseño general de la base de datos
    * Modelo entidad-relación (MER)
    * Modelo relacional y normalización (3FN)
    * Descripción del diccionario de datos y tablas principales
  * 3.1.7. Arquitectura del sistema (Patrón MVC multicapa, servicios REST y servicios externos integrados)
* **3.2. Aplicación de la metodología Scrum**
  * 3.2.1. Organización del equipo y responsabilidades en Scrum (Product Owner, Scrum Master, Developer)
  * 3.2.2. Elaboración del Product Backlog priorizado (Historias HU-01 a HU-07)
  * 3.2.3. Planificación y cronograma de los cuatro sprints
  * 3.2.4. Criterios de aceptación (Formato BDD / Gherkin) y Definición de Terminado (Definition of Done - DoD)
* **3.3. Implementación del sistema mediante sprints**
  * **3.3.1. Sprint 1: Acceso y usuarios**
    * 3.3.1.1. Objetivo del sprint
    * 3.3.1.2. Sprint Backlog (HU-01: Inicio de Sesión / HU-02: Gestionar usuarios)
    * 3.3.1.3. Especificación de casos de uso del módulo (Iniciar Sesión / Gestionar usuarios y roles)
    * 3.3.1.4. Diseño y modelado UML del módulo (Casos de uso, Clases, Secuencia, Actividades, Mockups e Interfaces)
    * 3.3.1.5. Implementación del código fuente y validaciones (Controladores, Modelos y Reglas de Validación)
    * 3.3.1.6. Pruebas del sprint (casos funcionales manuales de HU-01 y HU-02, y pruebas automatizadas con PHPUnit)
    * 3.3.1.7. Resultado e incremento entregable del sprint
  * **3.3.2. Sprint 2: Canchas y horarios**
    * 3.3.2.1. Objetivo del sprint
    * 3.3.2.2. Sprint Backlog (HU-03: Administrar canchas / HU-04: Gestionar horarios)
    * 3.3.2.3. Diseño y modelado UML del módulo (Casos de uso, Clases, Secuencia, Actividades, Mockups e Interfaces)
    * 3.3.2.4. Implementación del código fuente y validaciones (Controladores de canchas y horarios, Cálculo de disponibilidad)
    * 3.3.2.5. Pruebas del sprint (Caja negra y pruebas automatizadas con PHPUnit)
    * 3.3.2.6. Resultado e incremento entregable del sprint
  * **3.3.3. Sprint 3: Reservas y chatbot**
    * 3.3.3.1. Objetivo del sprint
    * 3.3.3.2. Sprint Backlog (HU-05: Administrar reservas / HU-06: Gestión de Chatbot NLP)
    * 3.3.3.3. Historia de Usuario 05 (HU-05): Administrar reservas (Casos de uso, Clases, Actividades, Secuencia, Modelo de Base de Datos, Tablas, Atributos, PK/FK y Normalizaciones)
    * 3.3.3.4. Historia de Usuario 06 (HU-06): Gestión de Chatbot NLP (Casos de uso, Clases, Actividades, Secuencia, Modelo de Base de Datos, Tablas, Atributos, PK/FK y Normalizaciones)
    * 3.3.3.5. Implementación del código fuente y validaciones (Transacciones de reserva y procesamiento conversacional)
    * 3.3.3.6. Pruebas del sprint (Caja negra funcional y pruebas automatizadas con PHPUnit)
    * 3.3.3.7. Resultado e incremento entregable del sprint
  * **3.3.4. Sprint 4: Dashboard y reportes**
    * 3.3.4.1. Objetivo del sprint
    * 3.3.4.2. Sprint Backlog (HU-07: Reportes y estadísticas)
    * 3.3.4.3. Historia de Usuario 07 (HU-07): Reportes y estadísticas (Casos de uso, Clases, Actividades, Secuencia, Modelo de Base de Datos, Tablas, Atributos, PK/FK y Normalizaciones)
    * 3.3.4.4. Implementación del código fuente y validaciones (Dashboard, agregación SQL/Eloquent y reportes PDF/Excel)
    * 3.3.4.5. Pruebas del sprint (Caja negra y pruebas automatizadas con PHPUnit)
    * 3.3.4.6. Resultado e incremento entregable del sprint
* **3.4. Integración y validación final del sistema**
  * 3.4.1. Pruebas de integración global entre módulos y servicios externos
  * 3.4.2. Pruebas funcionales de extremo a extremo (End-to-End)
  * 3.4.3. Matriz de trazabilidad y validación de requerimientos vs. HU-01 a HU-07
  * 3.4.4. Resultados finales y balance de cobertura de pruebas
