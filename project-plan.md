# Shopify and tracezilla Integration Guide — Project Plan

## Purpose

Create a practical, platform-neutral guide for integrating Shopify with
tracezilla. The primary audience is integration consultants. Technically
capable operations users are a secondary audience.

The guide should help readers:

1. configure Shopify and tracezilla;
2. choose an implementation platform;
3. copy, run, and adapt a tested cookbook example safely.

English is the only maintained documentation language. Each platform uses the
same Examples and Reference structure. Laravel is one platform among several,
although it currently covers more integration tasks than the others.

## Principles

- Organize documentation around short, executable cookbook recipes.
- Keep Shopify and tracezilla setup independent of implementation platforms.
- Keep each recipe focused on how to obtain and verify a result.
- Put shared API concepts in Reference rather than a separate workflow layer.
- Start every integration with a read-only test or dry run.
- Clearly label operations that create or change data.
- Prefer a few useful, tested examples over shallow coverage of many platforms.
- Show only completed examples in the main navigation.
- Avoid taking responsibility for generic application deployment practices.
- Keep pages short enough to scan and link to focused details when necessary.

## Target information architecture

```text
Home

Getting Started
├── Project Scope
├── Choose an Implementation
└── Integration Overview

Setup
├── Shopify
│   ├── Partner Account
│   ├── Development Store
│   ├── Products and Locations
│   ├── Inventory Setup
│   └── Authorize the API
├── tracezilla
│   ├── tracezilla Account
│   ├── Create the Webshop Partner
│   ├── Add Test Inventory
│   └── Authorize the API
└── Validate Connections

Laravel
├── Examples
└── Reference

Make.com
├── Examples
└── Reference

TypeScript
├── Examples
└── Reference

Python
├── Examples
└── Reference

Reference
├── Configuration
├── Authentication
├── Pagination
├── Data Mapping
└── Synchronization Results

Troubleshooting
├── Shopify
├── tracezilla
├── Catalog and Inventory
└── Orders
```

Use the same Examples and Reference structure beneath every platform. This
keeps the navigation useful as the number of workflows and platforms grows.

## Workflow catalog

The existing Laravel commands establish the initial workflow inventory, but
their command names do not define the public documentation terminology.

| Canonical workflow | Current Laravel implementation |
|---|---|
| Validate Shopify locations | `CheckLocationsInShopify` |
| Read the Shopify catalog | `PullCatalogFromShopify` |
| Create or update Shopify products | `PushCatalogToShopify` |
| Compare Shopify and tracezilla SKUs | `TracezillaSkusFromShopifyCommand` |
| Synchronize inventory | `UpdateInventoryInShopify` |
| Import individual orders | `PullOrdersFromShopifyIndividual` |
| Import collected orders | `PullOrdersFromShopifyCollected` |

Each canonical workflow page should contain:

- purpose and appropriate use;
- direction of data flow and source of truth;
- prerequisites, permissions, and required configuration;
- platform-neutral API sequence;
- pagination and mapping rules;
- whether the workflow reads or changes data;
- safe validation or dry-run procedure;
- expected result;
- common failures and recovery;
- links to every completed implementation.

Each implementation page should contain only what differs for that platform:

- setup and secret configuration;
- copyable command, code, or scenario steps;
- how to run and verify it;
- platform-specific limitations and troubleshooting;
- a link back to the canonical workflow.

## Delivery plan

Work one chunk at a time. Complete its acceptance checks and review the rendered
documentation before starting the next chunk.

### Chunk 1 — Navigation skeleton

**Status:** Complete.

**Outcome:** The side menu represents the platform-neutral direction without
requiring all content to be rewritten at once.

Tasks:

1. Add top-level landing pages for Getting Started, Setup, Workflows,
   Implementation Examples, Reference, and Troubleshooting.
2. Rename `Part I — Setup and API Foundations` to `Setup`.
3. Replace `Part II — Laravel Reference` with a Laravel entry beneath
   Implementation Examples.
4. Rename `Part III — Other Platforms` to `Implementation Examples`.
5. Assign every existing page to its intended destination.
6. Preserve working URLs where practical or add redirects when paths change.
7. Build Jekyll and check the rendered navigation and internal links.

Acceptance checks:

- Laravel is no longer presented as the default integration architecture.
- Existing setup instructions remain reachable.
- No page exists under two competing navigation structures.
- The Jekyll site builds successfully.
- Internal links affected by the move work.

### Chunk 2 — Compare Catalogs pilot

**Status:** Complete.

**Outcome:** One workflow demonstrates the complete canonical-workflow and
implementation-example pattern.

Tasks:

1. Create the canonical `Compare Catalogs` workflow page.
2. Document exact SKU comparison, pagination, empty SKUs, duplicates, and the
   read-only result.
3. Link the existing Make.com implementation.
4. Link the existing TypeScript and Docker implementation.
5. Link the existing Python and Docker implementation.
6. Add or extract the equivalent Laravel implementation page.
7. Make terminology and expected results consistent across all four examples.
8. Run each locally testable implementation and build the documentation.

Acceptance checks:

- A reader can understand the workflow without reading Laravel code.
- Every implementation describes the same comparison behavior.
- All execution instructions are copyable and include a visible success result.
- The workflow is clearly labelled read-only.

**Review point:** Confirm that the page size, cross-links, and navigation feel
right before applying this pattern to the remaining workflows.

### Chunk 3 — Connection and location workflows

**Status:** Complete.

**Outcome:** Readers can validate credentials and location mappings before
attempting synchronization.

Tasks:

1. Create canonical Shopify and tracezilla connection-test workflows.
2. Create the canonical Shopify location-validation workflow.
3. Remove unhelpful response payload data from examples where possible.
4. Document the absence of a dedicated tracezilla API-version endpoint and
   link to the CTO-wishes note where appropriate for maintainers.
5. Consolidate authentication and location troubleshooting.

Acceptance checks:

- Connection tests expose no credentials and return only useful information.
- Location identifiers and mapping expectations are clear.
- Readers know the next safe workflow after validation.

### Chunk 4 — Inventory workflow

**Status:** Complete.

**Outcome:** Inventory synchronization has one canonical definition and a safe
Laravel implementation guide.

Tasks:

1. Create the canonical `Synchronize Inventory` workflow.
2. Define source of truth, SKU matching, location mapping, and quantity rules.
3. Document preview/dry-run behavior before writes.
4. Move reusable concepts into Reference rather than repeating them.
5. Connect the tracezilla test-inventory setup guide to this workflow.
6. Link the Laravel command and any other completed implementations.

Acceptance checks:

- The direction and consequence of every inventory write are explicit.
- A consultant can prepare a small test catalog automatically or manually.
- The guide separates customer mapping decisions from fixed API behavior.

### Chunk 5 — Catalog write workflows

**Status:** Complete.

**Outcome:** Product creation and updates are documented separately from the
read-only comparison.

Tasks:

1. Create canonical read-catalog and create/update-product workflows.
2. Document products, variants, SKUs, units, and identifiers.
3. Require a preview and one-record limit for the first write.
4. Link the relevant Laravel commands.
5. Add focused catalog troubleshooting.

Acceptance checks:

- Read and write operations cannot be confused.
- The first documented write is deliberately limited and verifiable.
- Duplicate and partial-failure behavior is described.

### Chunk 6 — Order workflows

**Status:** Complete.

**Outcome:** All maintained order commands are represented as canonical
workflows.

Tasks:

1. Document importing individual orders.
2. Document importing collected orders.
3. Explain webshop-partner setup, order selection, idempotency, and safe test
   boundaries.
4. Link each workflow to its Laravel implementation and tests.
5. Consolidate order troubleshooting.

Acceptance checks:

- Selection rules are visible without reading source code.
- Re-running a command and avoiding duplicate imports are addressed.

### Chunk 7 — Reference consolidation

**Status:** Complete.

**Outcome:** Repeated technical explanations have one stable location.

Tasks:

1. Consolidate authentication and secret-handling guidance.
2. Document Shopify and tracezilla pagination.
3. Document SKU, product, inventory, location, and order mappings.
4. Document structured synchronization results and partial failures.
5. Add a configuration-variable reference.
6. Remove duplication from workflow and implementation pages.

Acceptance checks:

- Workflow pages remain task-focused.
- Platform pages remain implementation-focused.
- Reference pages can be linked directly from code comments and
  troubleshooting entries.

### Chunk 8 — Documentation quality pass

**Outcome:** The reorganized guide is consistent and maintainable.

Tasks:

1. Fix stale terminology, spelling, and inconsistent capitalization.
2. Replace screenshots with durable textual instructions where the same style
   is already used elsewhere in the guide.
3. Verify Docker Compose startup instructions from the project root.
4. Check every internal link and navigation entry.
5. Confirm that commands match the current application signatures.
6. Add automated Jekyll build and internal-link checks if not already present.

Acceptance checks:

- The documentation builds without errors.
- No public page exposes an internal backlog or irrelevant deployment advice.
- A new reader can follow setup, choose a platform, and run a read-only example.

### Chunk 9 — Add the next platform only after review

**Outcome:** Add one integration style that provides a distinct learning value.

Recommended order:

1. n8n — self-hosted visual automation;
2. Google Apps Script — scheduled API work and Google Sheets reporting;
3. Cloudflare Workers — lightweight TypeScript serverless execution;
4. Microsoft Power Automate — enterprise Microsoft environments.

Tasks for any new platform:

1. Implement the read-only Compare Catalogs workflow first.
2. Use the same terminology and expected outcome as the canonical page.
3. Document secret storage, pagination, scheduling, and limitations.
4. Test the example against sandbox accounts.
5. Add it to the main navigation only after verification.

Do not add Ruby, Java, .NET, or another framework merely to increase the
language count. Add one when it has a maintainer, a user request, or a distinct
integration lesson not covered by the current examples.

## Current platform coverage

| Platform | Role | Current scope |
|---|---|---|
| Make.com | Visual automation | Read-only catalog comparison |
| Laravel | PHP application framework | Catalog, inventory, and order workflows |
| TypeScript and Docker | Portable JavaScript example | Read-only catalog comparison |
| Python and Docker | Portable scripting example | Read-only catalog comparison |

Support levels should describe the documented workflow rather than imply that
an example is a complete production connector:

- **Maintained implementation:** code and tests are maintained for its stated
  workflow.
- **Verified recipe:** UI-based or hosted-platform instructions are manually
  verified for a narrow workflow.
- **Candidate:** evaluated or planned, but not shown in the main navigation.

## Immediate next action

Start with **Chunk 1 — Navigation skeleton**. Do not rewrite every page during
the move. Once the new menu renders correctly, complete **Chunk 2 — Compare
Catalogs pilot** and review it before converting the remaining workflows.
