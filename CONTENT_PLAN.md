# Narrative-first 20 category content pack

## Summary

Rewrite all category Markdown files in natural Dutch prose, not checklist style, with clear seasonal timing and practical maintenance guidance for an existing private garden designed by a garden architect. Keep the existing YAML schema, file discovery, runtime, and validator behavior unchanged. Per category and per week, the number of active items must remain `0..N`, so a category may have no item, one item, or multiple items in the same week.

## Garden model and editorial intent

The content is written for established private gardens with a clear design structure already in place. These are attractive, layered gardens that do not need to be created from scratch, but do require thoughtful recurring maintenance to remain healthy, visually strong, and ecologically functional through the year.

The planting may combine native species and ornamental species. Trees and shrubs are mixed in purpose: some are grown for fruit or nuts, many contribute mainly to habitat value, structure, flowering, autumn colour, shelter, or seasonal character. Some gardens include a vegetable garden, others focus more strongly on ornamental planting or fauna value. The content must support all three axes substantially: edible gardening, ornamental quality, and habitat value.

The guidance should help a beginner care for a high-quality designed garden without undermining its structure or atmosphere. Maintenance advice should preserve beauty, seasonal expression, plant health, and biodiversity, rather than reduce the garden to generic upkeep.

## Core content rules

* Write all category files in Dutch.
* Keep the current YAML schema exactly as it is.
* Each category must be independently useful, so a reader can use one category on its own and still get complete guidance.
* Write only recurring yearly work, never one-time setup as a standard assumption.
* A small number of recurring or occasional additions may be included in the fauna category, such as adding or replacing habitat elements, but these should not appear as annual obligations.
* Do not add filler items to force weekly coverage.
* Empty category-weeks must remain empty.
* When several tasks genuinely belong in the same category and week, multiple items are allowed.
* When duplicate or near-duplicate guidance would occur, keep only the most complete, specific, and seasonally useful version.

## Narrative writing standard

Every item must use one flowing paragraph in `body: |`, written in 3 to 5 full sentences.

The paragraph structure is fixed:

1. Why now, explain why this week or period matters in seasonal or weather terms.
2. What to do, describe the practical action clearly and concretely.
3. What to watch, include inspection, prevention, or risk awareness.
4. Optional close, mention the expected benefit for plant health, harvest, structure, visual quality, or biodiversity.

Tone:

* slightly warm and companionable
* calm, clear, and practical
* beginner-friendly, without sounding simplistic
* respectful of the garden's designed character

## Gardening approach

The default approach is biological where possible, ecological where fully biological advice is not realistic, and pragmatic when a limited intervention makes the work substantially easier or more reliable. Chemical control is excluded.

The baseline is low-disturbance, soil-conscious maintenance with respect for planting design, habitat value, and long-term structure. Advice may include organic fertilizers or simple nutrient support where that clearly helps, but such measures should remain supportive rather than dominant.

Do not assume every gardener has a compost bin. Where relevant, give generic options, such as:

* compost, if available
* organic mulch materials
* bought compost
* organic fertilizer
* leaving material on site in useful form, where appropriate

Keep these options generic rather than brand-specific or overly technical.

## Taxonomy and compatibility

Use this exact 20-category set with Dutch filenames, Dutch `category_key`, and Dutch item IDs:

* `algemeen-onderhoud.md`
* `grotere-klussen.md`
* `bodem-en-mulch.md`
* `compost-en-kringloop.md`
* `regenwater-en-bewatering.md`
* `moestuin.md`
* `kruiden-en-theeplanten.md`
* `fruitbomen-en-kleinfruit.md`
* `potten-en-bakken.md`
* `borders-en-vaste-planten.md`
* `eenjarigen-en-bloembollen.md`
* `heesters.md`
* `hagen.md`
* `bomen.md`
* `klimplanten.md`
* `gazon.md`
* `vijver-en-water.md`
* `biodiversiteit-en-habitat.md`
* `terras-paden-en-erfafscheidingen.md`
* `winterbeeld-en-structuur.md`

Implementation changes:

* remove `content/categories/moestuin-en-kruiden.md`
* rewrite `content/categories/fruitbomen-en-kleinfruit.md` from scratch
* create or maintain the full 20-file set above

Keep category metadata in Dutch and keep identifiers unique across all files.

## Category purpose guidance

The categories should reflect the reality of established private gardens with different balances of ornamental, edible, and ecological value.

* `algemeen_onderhoud` covers recurring broad maintenance, inspection rounds, light seasonal tidying, and practical garden care that does not belong more precisely elsewhere.
* `grotere_klussen` covers heavier recurring work that appears in shorter seasonal windows and needs planning.
* `bodem_en_mulch` covers soil cover, mulch renewal, local soil improvement, moisture buffering, and modest fertility support.
* `compost_en_kringloop` covers handling of garden residues and nutrient cycling, both with and without a compost system.
* `regenwater_en_bewatering` covers watering judgment, deep watering, drought response, and sensible use of stored rainwater where available.
* `moestuin` covers productive vegetable growing as a substantial category.
* `kruiden_en_theeplanten` covers culinary and tea herbs as a separate and manageable plant group.
* `fruitbomen_en_kleinfruit` covers fruit trees, berry shrubs, trained fruit, and nut-bearing elements where relevant.
* `potten_en_bakken` covers containers, which often need more frequent water and feeding judgment.
* `borders_en_vaste_planten` covers mixed perennial and ornamental border planting in designed gardens.
* `eenjarigen_en_bloembollen` covers seasonal flowering accents and bulb management.
* `heesters`, `hagen`, `bomen`, and `klimplanten` remain separate so pruning, training, timing, and inspection can stay specific.
* `gazon` covers lawns in a broad way, including sunny lawns, shaded lawns, and mixed conditions.
* `vijver_en_water` covers pond care and planted water zones where present.
* `biodiversiteit_en_habitat` covers fauna support, wildlife water points, nesting opportunities, shelter features, seasonal care of habitat elements, and occasional additions or replacements where useful.
* `terras_paden_en_erfafscheidingen` covers hardscape-related maintenance.
* `winterbeeld_en_structuur` covers the designed winter framework, including stems, bark, seed heads, evergreen mass, and structural restraint in cutting back.

## Fauna and habitat category requirements

`biodiversiteit_en_habitat` must explicitly cover support for fauna in the garden and ways to improve the garden for them. This includes recurring guidance on:

* cleaning and refilling drinking or bathing bowls for birds
* maintaining shallow water points for insects
* checking, cleaning, repairing, or repositioning bird houses
* maintaining or replacing bee nesting blocks for metselbijen
* checking and maintaining egelhuisjes or sheltered corners
* preserving cover, nesting material, seed sources, and safe passage through the garden
* seasonal choices that increase nectar, berries, seed, shelter, and overwintering value

Occasional additions are allowed where they fit recurring garden care, for example adding an extra water point during dry periods or replacing a worn nesting block. These should be framed as optional improvements, not annual compulsory tasks.

Winter feeding for birds may be included, but only where it fits seasonal conditions and is framed carefully as supportive care rather than a universal obligation.

## Weekly item rule

Per category and week:

* zero items is valid
* one item is valid
* multiple items are valid

No filler tips. No forced uniformity. The app already hides empty categories, so content should reflect genuine seasonal need.

## Metadata standard

Keep the schema exactly as documented in the project README. Use:

* Dutch `category_key`
* Dutch `category_title`
* Dutch item `id`
* `start_year: 2026`
* recurring logic with `repeat_every_years`
* explicit week windows

ID format:

* lowercase ASCII
* hyphen-separated slug
* unique globally

Controlled metadata vocabulary:

`conditions`

* `droog_weer`
* `nat_weer`
* `vorstrisico`
* `vorstvrij`
* `hitte`
* `stormverwachting`
* `windstil`
* `regenverwachting`
* `langdurige_droogte`
* `na_oogst`
* `bladval`
* `rustperiode`

`tags`

* `zaaien`
* `planten`
* `uitplanten`
* `verspenen`
* `oogsten`
* `snoeien`
* `leiden`
* `aanbinden`
* `mulchen`
* `composteren`
* `bemesten`
* `watergeven`
* `bodemverbetering`
* `onkruidbeheersing`
* `plaagpreventie`
* `ziektepreventie`
* `habitat`
* `bestuivers`
* `vogels`
* `winterbescherming`
* `bloei`
* `structuur`
* `opruimen`
* `planning`
* `controle`

`garden_types`

* `alle`
* `eetbaar`
* `siertuin`
* `water`
* `biodiversiteit`
* `verharding`

## Writing requirements per item

Apply one standard to every item:

* `title` and `body` are in Dutch
* `body` always contains action, inspection, and prevention guidance
* advice is primarily biological or ecological, never chemical
* practical fallback is allowed where it clearly reduces failure or unnecessary effort
* content is for recurring maintenance only
* timing must fit Netherlands coastal and inland conditions
* wording must fit beginner gardeners while remaining useful in a designed private garden
* plant examples may be mentioned only in general terms, such as rozen, lavendel, salie, appel, aalbes, hazelaar, or siergrassen, without becoming species-list driven
* pruning advice should be conservative by default in most years, but some items may acknowledge that stronger renewal or corrective pruning is appropriate in certain years
* `priority` stays editorial and relative to the category, rather than following one rigid cross-category scale
* target item counts are guidance, not a hard requirement, and may shift where duplicate removal or empty weeks make a category naturally leaner

## Lawn guidance requirement

`gazon` must reflect a full range of real garden situations:

* sunny lawns
* shaded lawns
* mixed sun and shade
* lawns used mainly for appearance
* lawns used more actively
* areas that may thin out under drought, moss pressure, shade, or compaction

The category should help the reader make seasonal choices without assuming one ideal lawn type.

## Interfaces and schema

* no PHP interface changes
* no validator logic changes
* existing content schema stays exactly:
  `category_key`, `category_title`, `sort_order`, `items[].id`, `title`, `weeks`, `start_year`, `repeat_every_years`, `priority`, `conditions`, `tags`, `garden_types`, `body`

## Implementation table

| File                                  | `category_key`                     | `sort_order` | Target items |
| ------------------------------------- | ---------------------------------- | -----------: | -----------: |
| `algemeen-onderhoud.md`               | `algemeen_onderhoud`               |           10 |           24 |
| `grotere-klussen.md`                  | `grotere_klussen`                  |           20 |           14 |
| `bodem-en-mulch.md`                   | `bodem_en_mulch`                   |           30 |           30 |
| `compost-en-kringloop.md`             | `compost_en_kringloop`             |           40 |           24 |
| `regenwater-en-bewatering.md`         | `regenwater_en_bewatering`         |           50 |           18 |
| `moestuin.md`                         | `moestuin`                         |           60 |          120 |
| `kruiden-en-theeplanten.md`           | `kruiden_en_theeplanten`           |           70 |           30 |
| `fruitbomen-en-kleinfruit.md`         | `fruitbomen_en_kleinfruit`         |           80 |           72 |
| `potten-en-bakken.md`                 | `potten_en_bakken`                 |           90 |           24 |
| `borders-en-vaste-planten.md`         | `borders_en_vaste_planten`         |          100 |           32 |
| `eenjarigen-en-bloembollen.md`        | `eenjarigen_en_bloembollen`        |          110 |           24 |
| `heesters.md`                         | `heesters`                         |          120 |           20 |
| `hagen.md`                            | `hagen`                            |          130 |           18 |
| `bomen.md`                            | `bomen`                            |          140 |           24 |
| `klimplanten.md`                      | `klimplanten`                      |          150 |           18 |
| `gazon.md`                            | `gazon`                            |          160 |           18 |
| `vijver-en-water.md`                  | `vijver_en_water`                  |          170 |           30 |
| `biodiversiteit-en-habitat.md`        | `biodiversiteit_en_habitat`        |          180 |           40 |
| `terras-paden-en-erfafscheidingen.md` | `terras_paden_en_erfafscheidingen` |          190 |           18 |
| `winterbeeld-en-structuur.md`         | `winterbeeld_en_structuur`         |          200 |           16 |

## Test plan

1. Validate all content with `php scripts/validate_content.php`
2. Validate strict mode with `php scripts/validate_content.php --strict`
3. Spot-check weeks with multiple items, one item, and zero items
4. Verify rendering still behaves correctly, including high-priority aggregation and hidden empty categories

## Assumptions and defaults

* Climate baseline: Netherlands, coastal and inland
* Audience: beginner gardeners
* Garden type: existing private gardens designed by a garden architect
* Strategy: low-disturbance, soil-conscious, balanced between ecology, beauty, and productivity
* Planting character: mixed native and ornamental planting, mixed shrubs and trees, possible vegetable garden, fruit and nuts in some gardens, strong fauna value in many gardens
* Output scope: content files only, no PHP or validator changes
