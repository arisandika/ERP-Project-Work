# INVENTORY MODULE - EXPLORATION & ANALYSIS REPORT

Generated: 2024-06-19  
Module: Inventory Management  
Status: Repository Analysis Complete - Ready for Testing

---

## 📊 STRUCTURE OVERVIEW

### Database Tables (Models Identified)
```
nx_products              → Product Model
nx_categories          → Category Model
nx_units               → Unit Model
nx_warehouses          → Warehouse Model
nx_product_stock       → ProductStock Model
nx_stock_transactions  → StockTransaction Model
nx_serial_numbers      → SerialNumber Model
nx_packages           → Package Model
nx_package_items      → PackageItem Model
nx_services           → Service Model
```

---

## 🔍 KEY MODELS & RELATIONSHIPS

### 1. **Product Model** (Table: `nx_products`)
**Key Fields:**
- `product_code` - Auto-generated: BRG-XXXXXX format
- `product_name` - Product name
- `category_id` - FK to Category
- `unit_id` - FK to Unit (pcs, box, etc)
- `min_stock` - Minimum stock threshold
- `price` - Internal price
- `purchase_price` - Cost price
- `selling_price` - Sale price
- `image_path` - Product image URL
- `is_serialized` - Boolean: Enable serial tracking
- `is_web_published` - Boolean: Publish to web

**Key Business Logic:**
```php
// Auto-generate product code saat create
- Before Save: TEMP-xxxxx (temporary)
- After Save: BRG-000001 format (final)

// Total stock dari semua warehouse
$total_stock = sum of (qty_available per warehouse)

// Low stock detection
$is_low_stock = total_stock < 10

// Stock status levels
- OUT_OF_STOCK: qty <= 0
- CRITICAL: qty <= 5
- LOW: qty <= 10
- AVAILABLE: qty > 10
```

**Relations:**
- `HasMany` → serialNumbers
- `HasMany` → productStocks (stock per warehouse)
- `HasMany` → stockTransactions
- `BelongsTo` → category
- `BelongsTo` → unit

**Key Methods:**
- `getLowStockProducts()` - Get products < 10 units
- `isLowStockInWarehouse($warehouseId)` - Check per warehouse
- `getStockStatusLabel()` - Return status text
- `getStockStatusColor()` - Return badge color

---

### 2. **ProductStock Model** (Table: `nx_product_stock`)
**Key Fields:**
- `product_id` - FK to Product
- `warehouse_id` - FK to Warehouse
- `qty_available` - Stock siap jual (available)
- `qty_reserved` - Stock yang sudah di-reserve (SO confirmed)
- `qty_on_delivery` - Stock sedang dikirim (on transit)

**Business Logic:**
```
Total Stock in Warehouse = qty_available + qty_reserved + qty_on_delivery

Stock States:
1. qty_available    → Ready to sell
2. qty_reserved     → Already allocated to SO (not available for new order)
3. qty_on_delivery  → On transit (reserved)
```

**Relations:**
- `BelongsTo` → Product
- `BelongsTo` → Warehouse

---

### 3. **StockTransaction Model** (Table: `nx_stock_transactions`)
**Key Fields:**
- `product_id`, `warehouse_id` - References
- `serial_number_id` - For tracked items
- `transaction_code` - Auto-generated reference
- `reference_number` - PO/SO/DO reference
- `mutation_type` - Type of transaction
- `transaction_date` - When it happened
- `type` - 'masuk' (in) or 'keluar' (out)
- `quantity` - Amount
- `price` - Unit price
- `total_price` - quantity × price
- `stock_before`, `stock_after` - Before/after quantities
- `notes` - Description
- `created_by` - User who created

**Mutation Types (Transaction Classification):**
```
1. stock_in         → Goods received (GR)
2. adjustment_in    → Stock adjustment (add)
3. reserve          → Order confirmed (SO)
4. delivery         → Shipment started (DO)
5. complete         → Delivery completed (items removed from inventory)
6. cancel           → Reservation cancelled (items back to available)
7. adjustment_out   → Stock adjustment (reduce/loss)
```

**Key Business Logic:**
```php
// Validation on Create
- quantity > 0 (harus lebih dari 0)
- product_id & warehouse_id wajib diisi
- auto-set transaction_date = now()

// DB Transaction (Lock for Update)
- Prevents race conditions
- Uses pessimistic lock (lockForUpdate)

// Stock Updates per Mutation Type
stock_in:
    qty_available += qty
    
adjustment_in:
    qty_available += qty
    
reserve:
    CHECK: qty_available >= qty
    qty_available -= qty
    qty_reserved += qty
    
delivery:
    CHECK: qty_reserved >= qty
    qty_reserved -= qty
    qty_on_delivery += qty
    
complete:
    CHECK: qty_on_delivery >= qty
    qty_on_delivery -= qty
    (FINAL REMOVE FROM INVENTORY)
    
cancel:
    CHECK: qty_reserved >= qty
    qty_reserved -= qty
    qty_available += qty
    
adjustment_out:
    CHECK: qty_available >= qty
    qty_available -= qty
```

**IMMUTABILITY:**
- Cannot UPDATE transactions (Prevents tampering with history)
- Cannot DELETE transactions (Audit trail must be intact)
- Throws Exception if attempted

**Relations:**
- `BelongsTo` → Product, Warehouse, SerialNumber, User

---

### 4. **Warehouse Model** (Table: `nx_warehouses`)
**Key Fields:**
- `warehouse_name` - Name
- `location` - Address
- `manager_name` - Warehouse manager
- `phone` - Contact
- `maps_url` - Google Maps link
- `is_active` - Boolean: Active/Inactive

**Key Methods:**
```php
getWarehouseCodeAttribute()   → WH-0001, WH-0002, etc
getTotalProductsAttribute()   → Count of unique products
getTotalStockAttribute()       → Sum of all qty (available + reserved + on_delivery)
hasStocks()                    → Boolean: Has any stock?
getLowStockItemsAttribute()   → Get items < 10 units
scopeActive($query)           → Query only active warehouses
```

**Relations:**
- `HasMany` → ProductStock

---

### 5. **Category Model** (Table: `nx_categories`)
**Fields:**
- `name` - Category name
- `description` - Description

**Relations:**
- `HasMany` → Products
- `HasMany` → Services

---

### 6. **Unit Model** (Table: `nx_units`)
**Fields:**
- `name` - Unit name (pcs, box, carton, etc)
- `symbol` - Symbol (pcs, bx, etc)
- `description` - Description

**Relations:**
- `HasMany` → Products

---

### 7. **SerialNumber Model**
**Purpose:** Track individual items (for high-value products)
**Relation:**
- `BelongsTo` → Product

---

## 🎯 KEY WORKFLOWS TO TEST

### Workflow 1: Stock In (Goods Receipt)
```
Precondition: Warehouse active, Product exist

Steps:
1. Create StockTransaction with:
   - product_id, warehouse_id
   - mutation_type = 'stock_in'
   - quantity = 100
   - reference_number = 'GR-001'

2. System validates:
   ✓ Quantity > 0
   ✓ Product & warehouse exist
   ✓ DB lock acquired

3. System updates:
   ✓ ProductStock created/updated
   ✓ qty_available += 100
   ✓ transaction_date set
   ✓ created_by set to current user

4. Result:
   ✓ StockTransaction created with stock_before/after
   ✓ ProductStock.qty_available = 100
```

### Workflow 2: Reserve Stock (Sales Order Confirmed)
```
Precondition: ProductStock has qty_available = 100

Steps:
1. Create StockTransaction with:
   - mutation_type = 'reserve'
   - quantity = 30
   - reference_type = 'SalesOrder'
   - reference_id = 'SO-001'

2. System validates:
   ✓ qty_available >= 30 (YES: 100 >= 30)

3. System updates:
   ✓ qty_available = 70 (100 - 30)
   ✓ qty_reserved = 30
   ✓ stock_before = 100, stock_after = 70

Result: SO-001 has 30 units reserved (can't be sold to others)
```

### Workflow 3: Move to Delivery
```
Precondition: qty_reserved = 30

Steps:
1. Create StockTransaction with:
   - mutation_type = 'delivery'
   - quantity = 30
   - reference_type = 'DeliveryOrder'
   - reference_id = 'DO-001'

2. System validates:
   ✓ qty_reserved >= 30 (YES: 30 >= 30)

3. System updates:
   ✓ qty_reserved = 0 (30 - 30)
   ✓ qty_on_delivery = 30
   ✓ stock_before = 70, stock_after = 70 (qty_available unchanged)

Result: Items are in-transit, customer can't cancel
```

### Workflow 4: Complete Delivery
```
Precondition: qty_on_delivery = 30

Steps:
1. Create StockTransaction with:
   - mutation_type = 'complete'
   - quantity = 30

2. System validates:
   ✓ qty_on_delivery >= 30 (YES: 30 >= 30)

3. System updates:
   ✓ qty_on_delivery = 0 (30 - 30)
   ✓ qty_available UNCHANGED (was 70, still 70)
   ✓ FINAL INVENTORY REMOVAL

Final Result:
✓ ProductStock.qty_available = 70
✓ Product removed from inventory
✓ Revenue recorded
✓ Complete SO→DO→Inventory flow
```

### Workflow 5: Cancel Reservation
```
Precondition: qty_reserved = 30

Steps:
1. Create StockTransaction with:
   - mutation_type = 'cancel'
   - quantity = 30

2. System validates:
   ✓ qty_reserved >= 30

3. System updates:
   ✓ qty_reserved = 0
   ✓ qty_available += 30

Result: Items back to available (can sell again)
```

### Workflow 6: Stock Adjustment
```
Scenario A: Add stock (inventory found/correction)
- mutation_type = 'adjustment_in'
- qty_available increases

Scenario B: Remove stock (loss/damage)
- mutation_type = 'adjustment_out'
- Validate: qty_available >= quantity
- qty_available decreases
```

---

## 🚨 VALIDATION RULES TO TEST

### Product Level
```
✓ product_code must be unique
✓ product_name required
✓ category_id required
✓ unit_id required
✓ min_stock >= 0
✓ price >= 0
✓ purchase_price >= 0
✓ selling_price > purchase_price (ideally)
```

### Stock Transaction Level
```
✓ quantity > 0 (CRITICAL)
✓ product_id required
✓ warehouse_id required
✓ mutation_type valid enum
✓ For 'reserve': qty_available >= quantity
✓ For 'delivery': qty_reserved >= quantity
✓ For 'complete': qty_on_delivery >= quantity
✓ For 'cancel': qty_reserved >= quantity
✓ For 'adjustment_out': qty_available >= quantity
✓ transaction_date must be valid date
✓ IMMUTABLE: Cannot update/delete after create
```

### Warehouse Level
```
✓ warehouse_name required & unique
✓ location required
✓ is_active boolean
```

### Category Level
```
✓ name required & unique
```

### Unit Level
```
✓ name required & unique
✓ symbol required
```

---

## 📈 CALCULATED FIELDS & METHODS

### Product Accessors
```php
$product->total_stock          → Sum qty_available dari semua warehouse
$product->is_low_stock         → boolean (total_stock < 10)
$product->stock_threshold      → 10 (hardcoded)
$product->image_url            → image_path or default image
```

### Warehouse Methods
```php
$warehouse->warehouse_code     → WH-0001 format
$warehouse->total_products    → Count distinct products
$warehouse->total_stock       → Sum of all quantities
$warehouse->low_stock_items   → Products < 10 units
$warehouse->hasStocks()       → boolean
```

### StockTransaction
```
transaction->type = 'masuk' (in) or 'keluar' (out)
(Set automatically based on mutation_type)
```

---

## 🔒 CONSTRAINTS & BUSINESS RULES

### 1. Transaction Immutability
```
Once created, transactions CANNOT be:
- Updated (throws Exception)
- Deleted (throws Exception)
Reason: Audit trail integrity
```

### 2. Pessimistic Locking
```
When updating ProductStock:
→ DB lock acquired (lockForUpdate)
→ Prevents race conditions
→ Ensures consistency in concurrent operations
```

### 3. Stock Movement Flow
```
REQUIRED ORDER:
stock_in → reserve → delivery → complete
         → cancel (revert reservation)
```

### 4. Stock Levels Validation
```
Cannot reserve more than available
Cannot deliver more than reserved
Cannot complete more than on_delivery
Cannot adjust_out more than available
```

---

## 📋 FILAMENT RESOURCES (Admin Panel)

Registered Resources:
- `CategoryResource`
- `ProductResource`
- `UnitResource`
- `WarehouseResource`
- `PackageResource`
- `ServiceResource`
- `SerialNumberResource`
- `TransactionResource`
- `InventoryMonitoringResource`
- `StockReportResource`
- `TransactionReportResource`

---

## 🧪 READY-TO-TEST SCENARIOS

### High Priority
1. **Product Code Auto-Generation** - Verify BRG-XXXXXX format
2. **Stock In Flow** - Basic goods receipt
3. **Reserve Stock** - SO confirmation
4. **Stock Validation** - Prevent overselling
5. **Low Stock Alert** - Trigger notification

### Medium Priority
6. **Multi-Warehouse** - Stock transfer scenarios
7. **Adjustment** - Physical count corrections
8. **SerialNumber** - High-value item tracking
9. **Stock Status** - Color/label calculation
10. **Transaction History** - Immutability enforcement

### Edge Cases
11. **Concurrent Transactions** - Race condition prevention
12. **Partial Delivery** - Multiple DO for 1 SO
13. **Stock Cancellation** - Reserve reversal
14. **Zero Stock** - Out of stock handling
15. **Negative Scenarios** - Invalid quantity, missing fields

---

## 💾 TEST DATA SETUP

### Seed Data Needed
```
- Warehouse(s): Jakarta Main, Surabaya Branch
- Unit(s): pcs, box, carton
- Category(ies): Electronics, Furniture, etc
- Product(s): 5-10 sample products
- User(s): For created_by tracking
```

### Test Database
- Using SQLite in-memory (from phpunit.xml)
- Auto-seeded before each test
- Auto-cleaned after each test

---

## 🎬 NEXT STEPS FOR TESTING

1. **Create Factory Classes** (ProductFactory, WarehouseFactory, etc)
2. **Write Unit Tests** (Model methods, validations)
3. **Write Feature Tests** (Workflows, integrations)
4. **Generate Test Coverage** (Report)
5. **Execute & Document** (Results, findings)

---

## 📝 FILES TO CREATE/MODIFY

```
tests/Unit/Inventory/
  ├── ProductTest.php
  ├── StockTransactionTest.php
  ├── ProductStockTest.php
  ├── WarehouseTest.php
  └── CategoryUnitTest.php

tests/Feature/Inventory/
  ├── StockInFlowTest.php
  ├── ReservationFlowTest.php
  ├── DeliveryFlowTest.php
  ├── AdjustmentTest.php
  └── MultiWarehouseTest.php

database/factories/Inventory/
  ├── ProductFactory.php
  ├── WarehouseFactory.php
  ├── CategoryFactory.php
  ├── UnitFactory.php
  └── ProductStockFactory.php
```

---

## Status Summary

✅ **Repository Explored**  
✅ **Models Analyzed**  
✅ **Relationships Mapped**  
✅ **Business Logic Documented**  
✅ **Test Scenarios Identified**  

⏳ **Next Phase:** Automated Testing Execution

