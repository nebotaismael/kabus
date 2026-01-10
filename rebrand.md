Here is the comprehensive **Context Document** designed for an AI agent (like GitHub Copilot) to execute the rebranding of the **Kabus** marketplace to **Hecate Market**.

### **Context Document: Project "Hecate Market" Rebranding**

**Objective:**
Rebrand the existing Laravel-based "Kabus" marketplace to **"Hecate Market"**. This involves a complete visual overhaul (Purple to Red), name changes, font updates, and structural modifications to the Auth pages, Dashboards, and Footer.

---

### **1. Global Configuration & Branding**

**Target:** Rename the application globally.

* **File:** `.env` (and `.env.example`)
* **Action:** Change `APP_NAME=Kabus` to `APP_NAME="Hecate Market"`.


* **File:** `config/app.php`
* **Action:** Ensure `'name' => env('APP_NAME', 'Hecate Market'),` is set default.


* **File:** `resources/views/layouts/app.blade.php`
* **Action:** Update the `<title>` tag logic if hardcoded.
* **Action:** Replace the favicon link `<link rel="icon" ... href="{{ asset('images/kabus.png') }}">` with a new Hecate icon.



---

### **2. Visual Theme & CSS (The "Red" Shift)**

**Target:** Replace the current primary "Purple" theme (`#bb86fc`) with a "Red" theme (e.g., `#ff0000` or `#d32f2f`) and update typography.

* **File:** `public/css/styles.css` & `public/css/auth.css`
* **Color Replacement Strategy:**
* Find all instances of **`#bb86fc`** (Primary Purple) and replace with **`#d32f2f`** (Hecate Red).
* Find **`#96c`** (Hover Purple) and replace with **`#b71c1c`** (Darker Red).
* Find **`#3c3c3c`** (Borders/Backgrounds) -> Keep as is, or darken to `#2c2c2c` for higher contrast with red.


* **Font Replacement:**
* **Current:** `font-family: "Arial", sans-serif;`
* **Action:** Change to a unique font stack.
* *Suggestion:* `font-family: "Verdana", "Geneva", sans-serif;` or import a Google Font (e.g., *Roboto* or *Lato*) in `layouts/app.blade.php` and apply it here.


* **Specific Classes to Modify:**
* `.navbar` (border-bottom)
* `.navbar-btn`, `.navbar-icon-btn` (borders/active states)
* `.footer` (border-top)
* `.dashboard-card-title`, `.a-v-panel-title` (text colors)
* `::-webkit-scrollbar-thumb` (scrollbar color)





---

### **3. Authentication Pages (Login & Register)**

**Target:** Redesign to look unique and "secure".

* **File:** `resources/views/auth/login.blade.php`
* **Design Overhaul:**
* Wrap the form in a new container class `.hecate-login-box` styled with a thick Red border and a dark background (`#000000`).
* **Security Step Visuals:** Add a visual "Security Check" header above the form (e.g., "Verifying Connection..." static text or icon) to mimic the "security steps" mentioned.
* **Input Fields:** Change inputs to be transparent with only a bottom red border (`border-bottom: 2px solid #d32f2f;`) instead of the current full box.




* **File:** `resources/views/auth/register.blade.php`
* **Action:** Mirror the design changes from Login.
* **Captcha:** Style the `.auth-register-captcha-wrapper` to look like a "Terminal" command prompt (green text on black background, monospace font) to differ from the standard look.



---

### **4. Dashboard Customization**

**Target:** Blend with Red theme and modify layout structure for User, Vendor, and Admin.

* **Global Layout:** `resources/views/layouts/app.blade.php`
* **Action:** Add a persistent "Hecate Market Status: Online" ticker or bar at the top of the body to distinguish the dashboard look.


* **User Dashboard:** `resources/views/dashboard.blade.php`
* **Action:** Change the `.dashboard-grid` layout. Move the "Profile Information Card" from the left sidebar to a horizontal banner at the top of the content area.
* **Style:** Apply a "glassmorphism" effect or a subtle red glow (`box-shadow: 0 0 15px rgba(211, 47, 47, 0.2);`) to `.dashboard-card`.


* **Admin Panel:** `resources/views/admin/index.blade.php`
* **Action:** Change the grid columns. Instead of `repeat(auto-fit, minmax(250px, 1fr))`, make important cards (like "User Management" and "Disputes") double-width for emphasis.
* **Icons:** Replace the current SVG/PNG icons in `.a-v-panel-item` with FontAwesome icons (if available) or CSS-only shapes colored Red.


* **Vendor Panel:** `resources/views/vendor/index.blade.php`
* **Action:** Rename "Vendor Panel" to "Hecate Merchant Command".
* **Layout:** Group "Products" actions (Add Digital, Cargo, Dead Drop) into a single "Product Management" section with a dropdown or tabs, rather than separate cards, to declutter the interface.



---

### **5. Footer & New Pages**

**Target:** Unique footer with Disclaimer and Refund Policy.

* **File:** `resources/views/components/footer.blade.php`
* **Action:** Remove the "Javascript Warning" if strictly not needed, or restyle it to be less intrusive.
* **Add Links:** Add HTML links for Disclaimer and Refund Policy:
```html
<a href="{{ route('disclaimer') }}" class="footer-button">Disclaimer</a>
<a href="{{ route('refund-policy') }}" class="footer-button">Refund Policy</a>

```




* **New Files to Create:**
* `resources/views/disclaimer.blade.php`: Create a static blade file extending `layouts.app` containing standard market disclaimer text.
* `resources/views/refund-policy.blade.php`: Create a static blade file with the refund terms.


* **File:** `routes/web.php`
* **Action:** Register the new routes:
```php
Route::view('/disclaimer', 'disclaimer')->name('disclaimer');
Route::view('/refund-policy', 'refund-policy')->name('refund-policy');

```





---

### **6. Asset & Image Branding**

* **File:** `resources/views/components/navbar.blade.php`
* **Logo:** Update `<img src="{{ asset('images/kabus.png') }}" ...>` to point to a new logo file (e.g., `hecate.png`).


* **Folder:** `public/images/`
* **Action:** The agent needs to know that new branding images (Logo, Favicon, Hero banner) must be uploaded to replace `kabus.png`.