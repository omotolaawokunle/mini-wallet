# ADR 004: Use Tailwind CSS v4 for Frontend Styling

## Status

Accepted

## Date

2025-10-05

## Context

The frontend requires a modern, responsive, and maintainable styling solution. We need:

- Rapid UI development
- Consistent design system
- Responsive layouts
- Small bundle size
- Good developer experience
- Easy maintenance

We considered the following options:

### Option 1: Traditional CSS/SCSS

**Pros:**
- Full control
- No learning curve
- Familiar to all developers

**Cons:**
- Manual naming (BEM, etc.)
- Large CSS files
- Specificity conflicts
- Slower development
- Harder to maintain

**Verdict:** ❌ Too slow for modern development

### Option 2: CSS-in-JS (Styled Components, Emotion)

**Pros:**
- Component-scoped styles
- Dynamic styling
- TypeScript support

**Cons:**
- Runtime overhead
- Larger bundle size
- SSR complexity
- Learning curve

**Verdict:** ❌ Performance concerns

### Option 3: Bootstrap

**Pros:**
- Comprehensive components
- Well-documented
- Large community

**Cons:**
- Heavy framework
- Generic design
- jQuery dependency (v4)
- Harder to customize
- Larger bundle

**Verdict:** ❌ Too opinionated, hard to customize

### Option 4: Tailwind CSS v3

**Pros:**
- Utility-first approach
- Rapid development
- Responsive design built-in
- PurgeCSS integration
- Customizable
- Small production bundle

**Cons:**
- Learning curve
- Verbose HTML
- Configuration via JS file

**Verdict:** ✅ Good, but v4 is better

### Option 5: Tailwind CSS v4

**Pros:**
- All v3 benefits
- **CSS-first configuration** (no JS config)
- **Faster build times** (Oxide engine)
- **Vite plugin** (first-class support)
- **Better performance**
- **Modern CSS features**
- Improved DX

**Cons:**
- Relatively new (learning resources limited)
- Breaking changes from v3

**Verdict:** ✅ Best choice

## Decision

We chose **Tailwind CSS v4** (Option 5).

## Rationale

1. **CSS-First Configuration**: Define theme in CSS using `@theme` directive
2. **Performance**: Faster builds with Oxide engine
3. **Vite Integration**: First-class Vite plugin
4. **Modern Approach**: Leverages modern CSS features
5. **Small Bundle**: Only used utilities in production
6. **Rapid Development**: Utility classes for fast UI iteration
7. **Responsive Design**: Mobile-first breakpoints built-in
8. **Consistency**: Design system enforced via utilities
9. **Maintainability**: No CSS files to manage

## Implementation

### Installation

```bash
npm install @tailwindcss/vite@next tailwindcss@next
```

### Vite Configuration

```javascript
// vite.config.ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(), // Tailwind v4 Vite plugin
  ],
})
```

### CSS Configuration

```css
/* resources/css/app.css */
@import "tailwindcss";

/* Define custom theme */
@theme {
  /* Custom colors */
  --color-brand-primary: #3b82f6;
  --color-brand-secondary: #64748b;
  
  /* Custom spacing */
  --spacing-18: 4.5rem;
  
  /* Custom fonts */
  --font-family-sans: Inter, ui-sans-serif, system-ui;
}

/* Base styles */
@layer base {
  html {
    font-family: var(--font-family-sans);
  }
}

/* Component utilities */
@layer components {
  .btn-primary {
    @apply bg-blue-500 hover:bg-blue-600 text-white font-medium px-4 py-2 rounded-md transition-colors;
  }
}
```

### Vue Component Example

```vue
<template>
  <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
      {{ title }}
    </h1>
    <p class="text-gray-600 mb-6">
      {{ description }}
    </p>
    <button class="btn-primary">
      Click Me
    </button>
  </div>
</template>
```

## Key Features Used

### 1. Responsive Design

Mobile-first breakpoints:

```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
  <!-- Responsive grid -->
</div>
```

### 2. Utility Classes

Rapid styling without custom CSS:

```html
<div class="flex items-center justify-between p-4 bg-white rounded-lg shadow-md">
  <!-- Layout, spacing, colors, shadows -->
</div>
```

### 3. State Variants

Hover, focus, active states:

```html
<button class="bg-blue-500 hover:bg-blue-600 active:bg-blue-700 focus:ring-2 focus:ring-blue-500">
  Button
</button>
```

### 4. Dark Mode (Future)

Built-in dark mode support:

```html
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
  <!-- Auto dark mode -->
</div>
```

### 5. Component Extraction

For repeated patterns:

```css
@layer components {
  .card {
    @apply bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow;
  }
}
```

## Design System

### Color Palette

```css
@theme {
  /* Primary */
  --color-blue-500: #3b82f6;
  --color-blue-600: #2563eb;
  
  /* Secondary */
  --color-gray-500: #6b7280;
  --color-gray-600: #4b5563;
  
  /* Success */
  --color-green-500: #10b981;
  
  /* Error */
  --color-red-500: #ef4444;
}
```

### Spacing Scale

```css
@theme {
  --spacing-1: 0.25rem;  /* 4px */
  --spacing-2: 0.5rem;   /* 8px */
  --spacing-4: 1rem;     /* 16px */
  --spacing-6: 1.5rem;   /* 24px */
  --spacing-8: 2rem;     /* 32px */
}
```

### Typography

```css
@theme {
  --font-size-sm: 0.875rem;
  --font-size-base: 1rem;
  --font-size-lg: 1.125rem;
  --font-size-xl: 1.25rem;
  --font-size-2xl: 1.5rem;
}
```

## Consequences

### Positive

- ✅ Fast development (utility classes)
- ✅ Small production bundle (~10-20KB)
- ✅ Consistent design system
- ✅ Responsive by default
- ✅ No CSS naming conventions needed
- ✅ Easy to maintain
- ✅ Good documentation
- ✅ Large community
- ✅ CSS-first configuration (v4)
- ✅ Faster builds (v4)

### Negative

- ❌ HTML can be verbose
- ❌ Learning curve for team
- ❌ Need to learn utility names
- ❌ V4 is relatively new

### Neutral

- 🔸 Different paradigm from traditional CSS
- 🔸 Best with component frameworks (Vue, React)
- 🔸 Requires build step

## Best Practices Followed

### 1. Mobile-First Responsive

```html
<!-- Default mobile, then tablet, then desktop -->
<div class="text-base md:text-lg lg:text-xl">
  Responsive text
</div>
```

### 2. Component Composition

```vue
<template>
  <div class="card">
    <h2 class="card-title">Title</h2>
    <p class="card-text">Description</p>
  </div>
</template>

<style>
@layer components {
  .card {
    @apply bg-white rounded-lg shadow-md p-6;
  }
  .card-title {
    @apply text-xl font-bold text-gray-900 mb-2;
  }
  .card-text {
    @apply text-gray-600;
  }
}
</style>
```

### 3. Consistent Spacing

Use spacing scale consistently:

```html
<div class="p-4 md:p-6 lg:p-8">
  <!-- Consistent padding -->
</div>
```

### 4. Accessible Colors

Ensure sufficient contrast:

```html
<div class="bg-gray-900 text-white">
  <!-- High contrast -->
</div>
```

## Performance Optimization

### Production Build

```bash
npm run build
```

Tailwind v4 automatically:
- Removes unused utilities
- Minifies CSS
- Optimizes for production

**Result:** ~10-20KB CSS (vs ~3MB unoptimized)

### Critical CSS

For above-the-fold content, inline critical utilities:

```html
<style>
  /* Inlined critical CSS */
  .hero { /* ... */ }
</style>
```

## Migration from v3 to v4

If migrating from v3:

1. Remove `tailwind.config.js`
2. Move config to CSS with `@theme`
3. Install v4 Vite plugin
4. Update class names if needed

## Alternative Considered

If Tailwind doesn't work:

1. **UnoCSS**: Similar to Tailwind, faster builds
2. **Open Props**: CSS variables design system
3. **Panda CSS**: Build-time CSS-in-JS

## References

- [Tailwind CSS v4 Documentation](https://tailwindcss.com/docs)
- [Tailwind CSS v4 Release Notes](https://tailwindcss.com/blog/tailwindcss-v4-alpha)
- [Vite Plugin](https://tailwindcss.com/docs/installation/vite)
- [Utility-First CSS](https://tailwindcss.com/docs/utility-first)
- [Responsive Design](https://tailwindcss.com/docs/responsive-design)

