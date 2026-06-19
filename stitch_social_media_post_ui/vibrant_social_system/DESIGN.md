---
name: Vibrant Social System
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#464554'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#767586'
  outline-variant: '#c7c4d7'
  surface-tint: '#494bd6'
  primary: '#4648d4'
  on-primary: '#ffffff'
  primary-container: '#6063ee'
  on-primary-container: '#fffbff'
  inverse-primary: '#c0c1ff'
  secondary: '#5c5f61'
  on-secondary: '#ffffff'
  secondary-container: '#e0e3e5'
  on-secondary-container: '#626567'
  tertiary: '#4651b9'
  on-tertiary: '#ffffff'
  tertiary-container: '#606ad4'
  on-tertiary-container: '#fffbff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e1e0ff'
  primary-fixed-dim: '#c0c1ff'
  on-primary-fixed: '#07006c'
  on-primary-fixed-variant: '#2f2ebe'
  secondary-fixed: '#e0e3e5'
  secondary-fixed-dim: '#c4c7c9'
  on-secondary-fixed: '#191c1e'
  on-secondary-fixed-variant: '#444749'
  tertiary-fixed: '#e0e0ff'
  tertiary-fixed-dim: '#bdc2ff'
  on-tertiary-fixed: '#000767'
  on-tertiary-fixed-variant: '#2f3aa3'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
typography:
  headline-lg:
    fontFamily: Outfit
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg-mobile:
    fontFamily: Outfit
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Outfit
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  xs: 8px
  sm: 12px
  md: 16px
  lg: 24px
  xl: 32px
  gutter: 16px
  margin-mobile: 16px
  margin-desktop: 40px
---

## Brand & Style
The design system is centered on high-energy connectivity and effortless content discovery. It targets a modern, mobile-first audience that values both aesthetic polish and functional speed. The brand personality is optimistic, dynamic, and "content-first," ensuring that user media remains the focal point while the interface provides a premium, tactile framework.

The visual style is **Glassmorphism Lite**. This approach utilizes subtle background blurs and semi-transparent layers to create a sense of depth without overwhelming the user. It avoids the heavy frost of traditional glassmorphism in favor of "breathable" surfaces, high-quality whitespace, and vibrant accents that guide the eye toward interaction points.

## Colors
The palette is anchored by a dynamic indigo primary, used for core actions, active states, and brand moments. 

- **Primary (#6366F1):** The engine of the UI. Used for primary buttons, notifications, and active navigation icons.
- **Secondary (#F8FAFC):** A cool, soft slate-white used for page backgrounds and subtle section grouping.
- **Tertiary (#818CF8):** A lighter indigo used for hover states and secondary interactive elements to maintain monochromatic harmony.
- **Neutral (#64748B):** A balanced slate gray for secondary text, borders, and utility icons, ensuring high legibility against white backgrounds.

## Typography
The system employs a dual-font strategy to balance character with utility. **Outfit** is used for headlines to provide a modern, geometric energy that feels approachable and fresh. **Inter** is used for all body copy and UI labels, chosen for its exceptional readability and neutral tone, ensuring that long-form comments and captions are easy to consume.

Line heights are generous to prevent visual crowding in dense social feeds. For mobile-specific views, large headlines scale down to maintain a balanced information density while keeping the bold weight for hierarchy.

## Layout & Spacing
The layout follows a **fluid grid system** with a strong emphasis on consistent padding. 

- **Mobile:** A 4-column grid with 16px side margins and 16px gutters.
- **Desktop:** A 12-column grid centered at a max width of 1280px, with 40px margins.
- **Rhythm:** All spacing is derived from a 4px baseline. Components primarily use 16px (md) for internal padding to maintain a spacious, "breathable" feel.

Content cards should span the full width of the grid on mobile and adjust to 1/2 or 1/3 configurations on larger screens to prevent line lengths from becoming too wide for comfortable reading.

## Elevation & Depth
This design system uses a "Glassmorphism Lite" approach to hierarchy. Depth is achieved through three primary methods:

1.  **Backdrop Blurs:** Navigation bars and modal overlays use a 20px blur with a 70% opacity white fill. This keeps the background context visible while focusing the user's attention.
2.  **Ambient Shadows:** Surface-level cards use a "Soft-Indigo" shadow: `0 8px 30px rgba(99, 102, 241, 0.08)`. This tinting connects the shadow to the primary brand color for a more cohesive feel.
3.  **Layered Opacity:** Secondary surfaces (like search bars) use a 5% indigo tint on a white base rather than a traditional gray, keeping the UI feeling vibrant.

## Shapes
The shape language is friendly and highly rounded. A standard **16px (1rem)** corner radius is applied to all primary containers and cards to evoke an approachable, modern feel. 

- **Standard (rounded):** 16px for cards, images, and large containers.
- **Large (rounded-lg):** 24px for modals and featured content.
- **Pill (rounded-full):** Reserved for buttons, search inputs, and chips to clearly distinguish interactive elements from content containers.

## Components

- **Buttons:** Primary buttons are pill-shaped with the dynamic indigo background and white text. They should have a subtle 4px vertical lift on hover.
- **Glass Cards:** Use a white background with 80% opacity and a 1px solid border at 10% white opacity to define the edges against vibrant content.
- **Chips:** Used for tags and categories. These are pill-shaped with a light 5% indigo fill and primary indigo text.
- **Input Fields:** Search and text inputs use a pill shape with the Secondary color (#F8FAFC) as the fill and a 1px border that turns primary indigo on focus.
- **Lists:** Comment and notification lists are borderless, using vertical spacing and subtle dividers (#F1F5F9) to separate items.
- **Avatars:** Always circular with a 2px white "halo" border when overlaying images or glass surfaces to ensure separation.
- **Floating Action Button (FAB):** A high-elevation primary indigo circle used for the "Create" or "Post" action, typically fixed to the bottom right on mobile.