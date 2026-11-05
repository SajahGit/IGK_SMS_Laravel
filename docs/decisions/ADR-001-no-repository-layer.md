# ADR-001: No repository / DTO / Domain layers

Status: accepted. Context: SOP §12 deliberately omits Repositories, DTOs, Actions, Domain and Modules.
Decision: controllers stay thin and delegate business rules to `app/Services`; Eloquent is used directly.
Consequence: less indirection for a small team; revisit only if a second data source appears.
