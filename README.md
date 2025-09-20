# RealmCorp WordPress

Custom WordPress code for realmcorp.com

## Custom Plugins

### realm-community-lookup
Community search and building hub functionality for water management services.

**Features:**
- **Community Search**: Postcode-based lookup with manager information
- **Building Hub**: Comprehensive utility management portal with:
  - Hero section with building details and billing system integration
  - Service cards for move in/out, payments, direct debit, and support
  - Multi-utility rates display (electricity, water, gas) organized by utility type
  - Conditional functionality based on billing systems (StrataMax vs BlueBilling)

**Shortcodes:**
- `[realm_community_lookup]` - Main search interface
- `[building_hub_hero]` - Building information header with portal access
- `[building_hub_services]` - Service action cards
- `[building_hub_rates]` - Utility rates tables grouped by type

**CSV Data Sources:**
- `communities.csv` - Building and manager information
- `community_charges.csv` - Utility rates with header-based column mapping

**Templates:**
- Header-based CSV reading for reliable data extraction
- Responsive design with Poppins typography
- FontAwesome icons for professional interface
- Conditional rendering based on billing systems

## Themes

- **yith-wonder**: Modified YITH Wonder theme with custom building hub styles in `additional.css`