# Oracle Data Model

## Goal

This target schema is designed for the future `Laravel + Oracle` version of the chess association website.
It is organized close to **Boyce-Codd Normal Form (BCNF)** so that:

- one business fact is stored in one place
- lookup values are isolated
- legal and editorial workflows stay traceable
- media management stays maintainable after handover

## Main domains

### Accounts

- `member_account`
- `member_profile`
- `account_role`
- `account_status`

The account stores authentication and lifecycle.
The profile stores personal data that belongs to the member profile itself, not to authentication.

### Consent and compliance

- `visitor_cookie_consent`
- `member_consent`
- `consent_type`

This separation allows the site to keep a proof of cookie consent for anonymous visitors and legal or publication consent for authenticated members.

### Editorial workflow

- `article`
- `article_review`
- `publication_status`
- `review_decision_type`

The publication state is not hard-coded in the article table as free text.
It is normalized through lookup tables so moderation can evolve without schema drift.

### Media and legal rights

- `media_asset`
- `media_binary_payload`
- `media_external_reference`
- `media_rights_grant`
- `media_type`
- `media_usage_type`
- `media_rights_status`
- `media_storage_mode`
- `media_album`
- `media_album_item`
- `article_media`
- `product_media`

The model deliberately separates:

- media metadata
- binary payloads stored inside Oracle
- external storage references
- rights and publication authorizations

This is cleaner than mixing file storage, rights and usage context in one table.

### Merch

- `product`
- `product_category`
- `product_status`
- `product_price`
- `customer_order`
- `customer_order_item`
- `order_status`

Prices are historized separately from the product so you do not rewrite commercial history every time a price changes.
Order totals are intentionally computed from `customer_order_item` through a view instead of being duplicated in the order tables.

## Why this is close to BCNF

### 1. Authentication is separated from identity

`member_account` stores login facts.
`member_profile` stores profile facts.

That avoids putting all user concerns in one table.

### 2. Legal enumerations are normalized

Roles, account statuses, publication statuses, review decisions, media types, storage modes and product statuses all live in separate tables.

That avoids transitive dependencies like:

- `status_code -> status_label`
- `role_code -> role_label`

inside operational tables.

### 3. Media storage is not overloaded

An image or video can be stored:

- inside Oracle as a BLOB
- outside Oracle with a URI reference

Instead of nullable columns for every storage strategy in one row, storage is split into dedicated tables.

### 4. Rights are distinct from the file itself

The existence of a media file does not imply publication rights.
`media_rights_grant` stores that legal layer separately.

## Recommended migration path from the prototype

1. migrate `users.json` into:
   - `member_account`
   - `member_profile`
2. migrate `articles.json` into:
   - `article`
3. keep media uploads disabled until:
   - rights workflow
   - storage strategy
   - admin moderation
   are validated
4. only then add:
   - `media_asset`
   - `media_rights_grant`
   - `media_album`
   - merch tables

## Storage recommendation for images and videos

The cleanest production approach is:

- Oracle stores metadata, rights and relationships
- object storage or managed file storage stores the heavy binaries

If you must store files directly in Oracle, `media_binary_payload` is ready for that.
