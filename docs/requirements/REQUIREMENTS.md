# Requerimientos vigentes del sistema

La fuente canónica para actores, alcance y requerimientos generales es:

- [`docs/chapter3/ENTREGA_01_REQUISITOS.md`](../chapter3/ENTREGA_01_REQUISITOS.md)

Esta referencia sustituye la versión anterior, que mezclaba funciones implementadas con elementos planificados. En particular:

- Los roles vigentes son Administrador, Recepcionista y Cliente.
- No se consideran implementados el rol gerente, pagos, arquitectura multiagente ni auditoría formal.
- La recepción, las reservas presenciales y el check-in/check-out sí están implementados.
- El chatbot se documenta como motor NLP local con proveedor generativo opcional.
- Google Calendar es una integración opcional, no una condición de funcionamiento del sistema principal.

Los cambios de requisitos deben realizarse primero en la fuente canónica y propagarse después a UML, backlog, código y pruebas.
