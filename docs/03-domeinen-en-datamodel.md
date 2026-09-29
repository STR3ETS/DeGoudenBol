# 03 – Domeinen en datamodel

## Uitgangspunten (uit de briefing)

- Meerjarig vanaf dag één: bedrijven blijven bestaan; inschrijvingen en uitslagen hangen aan een editie.
- Het monster kent de deelnemer niet; die relatie bestaat alleen in de kluis.
- Alles wat per jaar kan verschillen is een editie-instelling; het beoordelingsmodel is geversioneerd.
- Publieke URL's gebruiken slugs of willekeurige id's (ULID), nooit oplopende nummers.
- Tijden in UTC opgeslagen, getoond in `Europe/Amsterdam`. Bedragen in centen, btw apart.
- Iedere privacygevoelige entiteit heeft een bewaartermijn en een anonimiseringsjob.
- Eén inschrijving per bedrijf (= productielocatie) per editie; verkooppunten met hetzelfde product vallen daaronder (besluit 16).
- Akkoord op voorwaarden wordt bewaard met versie en tijdstip.
- Auditlog is append-only met hashketen.

## Naamgeving (aanname)

Code, tabellen en kolommen in het **Engels**; UI, routes, lang-strings en documentatie in het **Nederlands**. Vaste vertalingen zodat de briefing en de code op elkaar blijven passen:

| NL (briefing / UI) | EN (code) | NL | EN |
|---|---|---|---|
| Editie | `Edition` | Erkenning | `Recognition` |
| Provincie | `Province` | Badge-asset | `BadgeAsset` |
| Bedrijf | `Company` | Socialkit | `SocialKit` |
| Inschrijving | `Entry` | Persbericht | `PressRelease` |
| Standplaats | `Location` | Nieuwsbericht | `NewsPost` |
| Aanleverslot | `DeliverySlot` | Cadeaubonactie | `VoucherCampaign` |
| Monster | `Sample` | Winnaar | `VoucherWinner` |
| Testnummer | `sample_number` | Cadeaubon | `Voucher` |
| Ontvangstregistratie | `Intake` | Verzilvering | `Redemption` |
| Sessie | `Session` | Pakket | `Package` |
| Panellid | `Panelist` | Rechtenmatrix | `PackageEntitlement` |
| Belangenconflict | `ConflictOfInterest` | Order / Factuurregel | `Order` / `InvoiceLine` |
| Uitserveerschema | `ServingSchedule` | Sponsor / Plaatsing | `Sponsor` / `Placement` |
| Scorekaart | `Scorecard` | Sponsorkoppeling ("Bakt met") | `SponsorLink` |
| Onderdeel / beoordelingsmodel | `Criterion` / `ScoringModel` | Goed doel / Voordracht | `Charity` / `Nomination` |
| Scorecontrole | `ScoreReview` | Reservering / Uitbetaling | `Reservation` / `Payout` |
| Uitslag | `Result` | Cursus / Boeking | `Course` / `Booking` |
| Koppeling (kluis) | `VaultLink` | Oefenbeoordeling | `PracticeAssessment` |
| Publicatiebatch | `PublicationBatch` | Auditlog | `AuditLog` |
| Goedkeuring | `Approval` | Voorlijst | UI-term voor de provinciale `RankingSnapshot` |
| Snapshot / Positie | `RankingSnapshot` / `RankingPosition` | Voorlopige / Definitieve Top 10 | snapshot-status `provisional` / `frozen` |
| Beslisronde | `TieBreakRound` | Correctiedossier | `CorrectionCase` |

## Database-indeling (aanname op basis van de lokale omgeving)

De briefing stelt PostgreSQL met een schema per domein voor, of MySQL als het pizzaplatform dat gebruikt. Laragon draait MySQL en `.env` staat al op MySQL; werkhypothese is dus **MySQL 8**. Vertaling van "schema per domein" naar MySQL:

| Database | Connectie | Inhoud | Gebruiker |
|---|---|---|---|
| `degoudenbol` | `mysql` (default) | Editie, deelnemers, ranking & publicatie, marketing, cadeaubonnen, commercie, goede doelen, platform | app-gebruiker |
| `degoudenbol_testing` | `testing` | Testketen: monsters, inname, sessies, panelleden, scorekaarten, scorecontrole, uitslagen | **eigen gebruiker** met rechten alleen op deze database; panel-API en scorecontrole gebruiken uitsluitend deze connectie |
| `degoudenbol_vault` | `vault` | Koppeling testnummer ↔ inschrijving, inzagelog | eigen gebruiker; kolommen versleuteld met `VAULT_ENCRYPTION_KEY` (los van `APP_KEY`) |

Geen foreign keys tussen databases; integriteit in de applicatielaag met ULID's. Als het toch PostgreSQL wordt: zelfde indeling als drie schema's in één database, connecties met `search_path`.

Lokaal vooralsnog één MySQL-server met drie databases en drie gebruikers; op productie kan de testketen op een aparte instance.

## De tien domeinen

### 1. Editie (`Edition`) – alles wat per jaar verschilt

- `editions`: `year`, `name`, `slug`, `status` (`draft`, `registration_open`, `testing`, `closed`, `frozen`, `published`, `archived`), plus datums: `registration_opens_at`, `first_test_day`, `last_test_day`, `freeze_at`, `main_publication_at`, `final_test_at`, `national_result_at`, `last_redeem_day`.
- `edition_settings` (of json op edition): `capacity_per_province`, `national_list_length` (5|10), `publish_threshold` (5.0), `min_valid_cards` (6), `max_samples_per_session` (8), `freshness_window_minutes` (180), `outlier_deviation_points` (15), `rounding_decimals` (1), `publication_schedule` (di/vr 12:00), `quiet_period_from`, `province_reveal_schedule` (12 tijden), `voucher_count_per_winner` (10), `voucher_value_cents` (4500), `voucher_max_per_email`, `tie_break_order`, `finalist_fallback` (`drop`|`next_in_line`), `logistics_mode` (`delivery`|`purchase`), `participation_model` (`A`|`B`), `pieces_per_entry` (8), `product_variant` (krenten/rozijnen), `show_panel_after_edition` (bool).
- `provinces`: de 12, vast (`name`, `slug`, `code`).
- `edition_provinces`: `capacity`, `reveal_at`, `partner_sponsor_id`, `press_release_id`.
- `scoring_models`: `edition_id`, `version`, `product_type` (oliebol; later appelbeignet), `is_active`.
- `scoring_criteria`: `scoring_model_id`, `code`, `name`, `description` (toelichting uit het dossier), `max_points`, `sort`, `tie_break_rank`.
- `test_locations`: naam, adres, capaciteit.
- `terms_versions`: `type` (`participation`, `voucher_campaign`, `privacy`, `image_consent`), `version`, `body`, `published_at`.

**Placeholder beoordelingsmodel 2026 (AANNAME – exacte lijst komt uit het Conceptdossier):**

| # | Onderdeel | Max | Tiebreak-volgorde |
|---|---|---|---|
| 1 | Smaak | 25 | 1 |
| 2 | Structuur en luchtigheid | 20 | 2 |
| 3 | Vulling en verhouding | 15 | 4 |
| 4 | Versheid | 10 | 3 |
| 5 | Korst en kleur | 10 | – |
| 6 | Bakgraad en vetopname | 10 | – |
| 7 | Geur | 5 | – |
| 8 | Uiterlijk en presentatie | 5 | – |

Zeker uit de briefing: Smaak 0–25, Structuur en luchtigheid 0–20, 15 punten over vulling, en de tiebreak-volgorde smaak → structuur en luchtigheid → versheid → vulling en verhouding. De rest is invulling tot 100 en wordt vervangen zodra het dossier er is. Het model zit in de seeder en de instellingen, niet in code.

### 2. Deelnemers (`Participants`)

- `companies`: `ulid`, `name`, `slug`, `kvk_number`, `type` (bakkerij, seizoenskraam, frituurkraam, snackbar, marktbakker), `founded_year`, `website`, `socials` (json), `logo`, `status`.
- `users` (deelnemersaccounts; magic link of wachtwoord) en `company_users` met `role` (`owner`, `staff`, `scanner`).
- `contacts`: contactpersoon per bedrijf.
- `entries`: `ulid`, `edition_id`, `company_id`, `province_id`, `status`, `package_id`, `terms_acceptance_id`, `delivery_slot_id`, `withdrawn_at`. Statussen: `registered` → `scheduled` → `received` → `numbered` → `scored` → `reviewed` → `linked` → `published` | `confidential`; zijpaden `freshness_expired`, `withdrawn`.
- `delivery_slots`: `edition_id`, `test_location_id`, `starts_at`, `ends_at`, `capacity`. Bij `logistics_mode = purchase`: `purchase_orders` in plaats van slots.
- `locations` (standplaatsen): `company_id`, adres, `postcode`, `city`, `province_id`, `lat`, `lng`, `is_primary`, `season_from`, `season_to`.
- `opening_hours` (`location_id`, `weekday`, `opens`, `closes`) en `opening_hour_exceptions` (`date`, `opens`, `closes`, `is_closed`, `label` bv. "Oudjaarsdag").
- `profiles`: openbare tekst per bedrijf (`story`, `tagline`, `specialties`, `photos`), `moderation_status` (`draft`, `pending`, `approved`), `approved_by`. Gaat mee naar volgende edities.
- `allergen_declarations` per entry (voor het panel: allergenen in het product).
- `terms_acceptances`: `user_id`, `company_id`, `terms_version_id`, `accepted_at`, `ip`.

### 3. Testketen (`Testing`) – eigen connectie, **alleen testnummers**

> Gebouwd op 25 september 2026. Afwijkingen van de tabel hieronder: de sessie heet `TestSession` (tabel `test_sessions`, koppeltabellen `session_samples` en `session_panelists`) om botsing met Laravels sessions te voorkomen; `serving_exclusions` hangt aan de sessie; `panelists` bevat de allergeencategorieën zelf (met `consent_at`); `results` heeft `total_raw` én `total`. De ontvangst- en nummeracties leven in een apart Intake-domein (`app/Domain/Intake`), zie docs/05 §9. Eén inschrijving kan meerdere monsters hebben (provinciale ronde, beslisronde B01…, finale F01…); de kluis heeft daarom één koppeling per monster. In het rankingdomein zijn `tie_break_rounds` (met `scores` en `outcome_order`), `finalists` (herkomst, plaats in de provincie, vervanging) en `correction_cases` (origineel en nieuw resultaat, twee goedkeuringen) toegevoegd; `publication_items` kent een `round` en `edition_province` een `revealed_at`.

- `samples`: `sample_number` (0001…; F01…; beslisronde B01…), `edition_id`, `round` (`provincial`, `final`, `tie_break`), `status`. **Geen** `company_id`, **geen** `province_id`.
- `intakes`: `sample_id`, `received_at`, `temperature_c`, `piece_count`, `photo_path`, `received_by`, `freshness_expires_at`, `label_printed_at`.
- `sessions`: `edition_id`, `test_location_id`, `starts_at`, `ends_at`, `status`, `max_samples`; `session_samples` (`serving_order`).
- `panelists`: `user_id`, `display_code` (P07), `pool_status`, `fee_per_session_cents`; `panelist_allergen_profiles` (gezondheidsgegevens: alleen categorieën, `consent_at`, wissen na editie).
- `serving_assignments`: `session_id`, `panelist_id`, `sample_id`, `order`, `status`. Uitsluitingen (`serving_exclusions`: `panelist_id`, `sample_id`, `reason_code` = `conflict`|`allergen`) worden door een systeemproces met kluistoegang aangemaakt; de naam staat er niet in.
- `scorecards`: `uuid` (door het apparaat gegenereerd), `sample_id`, `panelist_id`, `session_id`, `scores` (json `{criterion_code: int}`), `strengths`, `opportunities`, `submitted_at`, `hash`, `source` (`app`|`paper`), `is_valid`, `invalidated_reason`. Na indienen onveranderlijk.
- `paper_entries`: dubbele invoer van papieren kaarten; pas geldig als twee invoeren gelijk zijn.
- `score_corrections`: `scorecard_id`, `reason`, `before`, `after`, `requested_by`, `approved_by` (scorecontrole).
- `results`: `sample_id`, `scoring_model_version`, `card_count`, `criterion_averages` (json), `total_raw`, `total` (1 decimaal), `flags` (json: outliers, missing, freshness), `status` (`pending`, `final`), `finalized_by`, `finalized_at`.
- `reference_samples`: bekende referentiemonsters om paneldrift te zien (optioneel).

In het hoofddomein staat `panelist_conflicts` (`panelist_id`, `company_id`, gemeld door panellid) – dat is de bron voor de uitsluitingen.

### 4. Kluis (`Vault`) – eigen connectie, versleuteld

- `vault_links`: `sample_id`, `entry_id` (encrypted), `created_by`, `created_at`.
- `vault_access_logs`: append-only; `user_id` of `system`, `action` (`read`, `create`, `schedule`, `link`), `sample_id`/`entry_id`, `reason`, `ip`, `created_at`. Melding bij ongebruikelijke inzage (drempel per uur).

Toegang uitsluitend via één `VaultService` die altijd logt en die alleen de rollen Ontvangst & registratie, Publicatie (na definitieve score) en systeemjobs mogen aanroepen.

### 5. Ranking en publicatie (`Ranking`)

- `publication_batches`: `edition_id`, `scheduled_at`, `status` (`draft`, `pending_approval`, `approved`, `published`), `submitted_by`.
- `publication_items`: `batch_id`, `entry_id`, `result_snapshot` (json-kopie: totaal, deelscores), `visibility` (`public`|`confidential`), `province_id`.
- `approvals`: polymorf (`batch`, `correction_case`), `user_id`, `approved_at`. Regel: twee verschillende users, geen van beiden de `submitted_by`.
- `ranking_snapshots`: `edition_id`, `scope` (`province:{id}` | `national`), `round`, `engine_version`, `computed_at`, `published_at`, `input_hash`, `status` (`provisional`, `frozen`, `final`), `batch_id`.
- `ranking_positions`: `snapshot_id`, `entry_id`, `position`, `total`, `tie_group`, `label` (`new`, `up`, `down`, `same`), `needs_tie_break` (bool).
- `tie_break_rounds`: `edition_id`, `province_id`, `scope` (`position_1` | `top10_boundary`), `entry_ids`, `session_id` (testdomein), `outcome_order`, `status`.
- `correction_cases`: `entry_id`, `reason`, `original_result`, `new_result`, `status`, `submitted_by`, twee approvals, `snapshot_after_id`. Origineel blijft bewaard.
- `confidential_reports`: `entry_id`, `strengths`, `opportunities`, `course_suggestion`, `generated_at` (eventueel eerste versie via Claude API, altijd menselijke eindredactie).

### 6. Marketing

- `recognitions`: `code` (verificatie, onraadbaar), `entry_id`, `company_id`, `type` (`participant`, `tested`, `top10`, `province_winner`, `national_list`, `national_winner`), `edition_id`, `province_id`, `valid_from`, `valid_until`, `embargo_until`, `status`.
- `badge_assets`: `recognition_id`, `format` (svg/png × licht/donker, pdf poster/sticker/toonbankkaart), `path`, `generated_at`.
- `social_kits`: `entry_id`, `milestone`, `assets` (json per formaat), `texts` (json), `zip_path`, `embargo_until`, `download_count`, `share_count`.
- `press_releases`: `edition_id`, `province_id`, `milestone`, `body`, `embargo_until`, `kit_token`. Gebouwd 29 sep: ook `title`, `slug`, `published_at`, `sent_at`, `generated_at`, `updated_by`; geen `kit_token` (ondertekende URL's). `media_contacts` zoals beschreven, provincie leeg = landelijk.
- `news_posts`: `title`, `slug`, `body`, `published_at`, SEO-velden, `video_url`.
- `media_contacts`: `name`, `outlet`, `email`, `province_id`.
- `share_events`: metingen (nooit input voor scores).

### 7. Cadeaubonnen (`Vouchers`)

- `voucher_campaigns`: `edition_id`, `company_id` (Top 10-ondernemer), `starts_at`, `winners_deadline_at`, `last_redeem_day`, `terms_version_id`, `selection_method`, `status`.
- `voucher_winners`: `campaign_id`, `first_name`, `last_initial`, `email`, `claim_token`, `claimed_at`, `consent_photo`, `consent_public_name`, `anonymized_at`.
- `vouchers`: `code` (leesbaar, bv. `GB26-GLD-7K3M`), `qr_token_hash` (128-bit token, gehasht opgeslagen), `campaign_id`, `winner_id`, `value_cents`, `status` (`issued`, `redeemed`, `expired`, `void`), `issued_at`, `expires_at`, `pdf_path`.
- `redemptions`: `voucher_id`, `redeemed_by`, `redeemed_at`, `method` (`scan`|`manual`), `photo_path`, `refusal_reason`.
- `voucher_shortfalls`: tekort per ondernemer, aangevuld door Bonbeheer, doorgefactureerd.

Gebouwd 28 sep, afwijkingen: `voucher_campaigns` heeft ook `entry_id`, `province_id`, `winner_count`, `voucher_value_cents` (vastgezet uit de editie-instellingen op het moment van aanmaken) en `opened_notified_at`; `voucher_winners.claim_token` heet `claim_token_hash` (sha256, nooit de platte waarde) en heeft `terms_version_id`; `vouchers` heeft `redeemed_at` en geen `pdf_path` (de bon is de webpagina `/bon/{token}`); `redemptions` slaat ook geweigerde pogingen op (`result`, `refusal_reason`, `voucher_campaign_id`) en heet `redeemed_by` daar `participant_user_id`; `voucher_shortfalls` heeft `count`, `note`, `created_by`, `invoiced_at`.

### 8. Commercie en financiën (`Commerce`)

- `packages`: `edition_id`, `name` (zonder rangnummer), `price_cents`, `vat_rate`, `description`, `stock_per_province`, `sort`; `package_entitlements` (rechtenmatrix: welke marketingdiensten).
- `products` (sponsorcatalogus): `code` (`homepage`, `participant_link`, `national_top`, `province_partner`, `custom`), `price_cents`, `extra_link_price_cents`, `vat_rate`, `available_from_phase`.
- `orders`: polymorf (`entry`, `sponsor`, later `booking`), `status`, `total_cents`; `invoice_lines`: `description` (inhoud, **nooit een positie**), `amount_cents`, `vat_rate`, `counts_for_charity` (bool), `product_id`/`package_id`.
- `invoices`: `number`, `external_id` (Moneybird/Exact), `pdf_path`, `status`, `due_at`; `payments` (`mollie_payment_id`, `status`, `paid_at`); `credit_notes`; `reminders`.
- `sponsors`: `name`, `logo`, `url`, contact; `placements`: `sponsor_id`, `product_id`, `location` (`homepage`, `province:{id}`, `entry:{id}`, `national_top`, `final`), `starts_at`, `ends_at`, `is_exclusive`, `status`; `sponsor_links`: `sponsor_id`, `company_id`, `confirmed_by_company_at` ("Bakt met [sponsor]").

Gebouwd 29 sep, afwijkingen: `products` heeft `edition_id`, `name`, `description`, `is_custom`, `sort` (geen aparte `package_entitlements`); `sponsors` heeft `ulid`, `slug`, `logo_path`, `kvk_number`, `billing_address` (json), `status`; `placements` heeft ook `edition_id`, `province_id`, `entry_id`, `label`, `price_cents` (vastgezet bij reserveren) en `order_line_id`, en de extra locaties `national` en `charities`; `sponsor_links` heeft `edition_id`, `placement_id`, `requested_at`, `declined_at`.

### 9. Goede doelen (`Charities`)

- `charities`: `name`, `kvk_or_rsin`, `is_anbi`, `province_id`, `category`, `motivation`, `status` (`nominated`, `reviewing`, `approved`, `alternative`, `linked`, `paid_out`), `review_checklist` (json, criteria uit dossier).
- `nominations`: door `company` (portaal) of `sponsor` (backoffice).
- `reservations`: `edition_id`, `invoice_line_id`, `charity_id` of `regional_pot_province_id`, `amount_cents` (10 % van grondslag), `basis` (`invoiced`|`received`).
- `payouts`: `charity_id`, `amount_cents`, `paid_at`, openbaar op de goede-doelenpagina.

Gebouwd 29 sep, afwijkingen: geen aparte `nominations`-tabel; de voordrager staat als `nominated_by` (morph: `Company` of `Sponsor`) op `charities`, met `slug`, `website`, `review_note`, `reviewed_by`, `reviewed_at`. Tabellen heten `charity_reservations` (met `payer` morph, `basis_cents` én `amount_cents`, `regional_pot_province_id` blijft ook na koppeling staan) en `charity_payouts` (met `edition_id`, `reference`, `note`, `created_by`).

### 10. Academie (`Academy`, 2027)

`courses`, `course_dates` (capaciteit, wachtlijst), `bookings` (Mollie), `practice_assessments` (zelfde scorekaart, eigen domein, vertrouwelijk, telt nooit mee), `coachings` (docent ↔ bedrijf → maakt automatisch een `panelist_conflict` aan), certificaten als pdf.

### 11. Platform

`users` (medewerkers) met rollen en rechten, tweestapsverificatie (passkey of TOTP), `audit_logs` (append-only: `actor`, `action`, `subject`, `payload`, `prev_hash`, `hash`), `notifications`, `media` (bestanden), `settings`, jobs/queues.

## Rolmodel → rechten

| Rol | Hoofd-DB | Testketen | Kluis | Verboden combinatie (per editie) |
|---|---|---|---|---|
| admin | alles behalve scores | lezen | nee | – |
| intake (ontvangst & registratie) | entries lezen | intakes, samples schrijven | **schrijven + lezen (gelogd)** | panelist |
| coordinator (testcoördinatie) | nee | sessions, serving schrijven | nee | – |
| panelist | nee | alleen eigen scorecards schrijven | nee | intake, publisher |
| reviewer (scorecontrole) | nee | results, corrections | nee | – |
| publisher (publicatie) | batches, approvals | results lezen | lezen na definitieve score (gelogd) | panelist |
| communication | gepubliceerde data, news, profiles, kits | nee | nee | – |
| voucher_manager | vouchers | nee | nee | – |
| finance | orders, invoices, reservations | nee | nee | – |
| participant / staff / scanner | eigen company | nee | nee | – |
| sponsor | eigen sponsor | nee | nee | – |

Policies dwingen af: `Approval` vereist `user_id ≠ batch.submitted_by` en `≠` andere approval; `RoleAssignment` weigert verboden combinaties binnen dezelfde editie; ranking-services hebben geen imports uit Commerce/Marketing.

## Harde regels → mechanisme

| Regel | Waar afgedwongen |
|---|---|
| Panellid nooit ook ontvangst/registratie/publicatie | `RoleAssignment`-validatie + databasecheck per editie |
| Panel-API alleen testnummers | Aparte connectie + aparte modellen zonder relatie naar `Company`; API-resources exposen alleen `sample_number` |
| Scorekaart vergrendeld | Model zonder `update`; hash bij insert; correcties als aparte rijen |
| Twee goedkeurders ≠ indiener | `ApprovalPolicy` + unieke index `(approvable, user_id)` + check in `PublishBatch`-action |
| Belangenconflict → uitsluiting zonder naam | `GenerateServingSchedule`-job vertaalt `panelist_conflicts` via kluis naar `serving_exclusions` |
| Kluisinzage gelogd | Alle toegang via `VaultService`; directe query's op de connectie verboden (architectuurtest) |
| Betaling/sponsoring niet in ranking | Ranking-domein heeft geen toegang tot `orders`/`placements`; architectuurtest op imports |
| Panelleden zien eigen scores niet terug | API geeft na `submitted_at` geen kaartinhoud meer terug |
