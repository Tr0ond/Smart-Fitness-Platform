<template>
  <div class="bo-cuc-loi">
    <main class="bo-cuc-loi__main">
      <div
        class="bo-cuc-loi__thuong-hieu"
        aria-label="Smart Fitness Platform"
      >
        <span
          class="thuong-hieu__dau-hieu"
          aria-hidden="true"
        >
          <svg
            viewBox="0 0 32 32"
            fill="none"
          >
            <path
              d="M7 6v20M25 6v20M4 11h6M22 11h6M4 21h6M22 21h6"
              stroke="currentColor"
              stroke-width="2.4"
              stroke-linecap="round"
            />
            <path
              d="M12 16h8"
              stroke="currentColor"
              stroke-width="2.4"
              stroke-linecap="round"
            />
          </svg>
        </span>
        <span>SMART FITNESS</span>
      </div>
      <section
        class="bo-cuc-loi__the"
        aria-label="Thông báo điều hướng"
      >
        <slot />
      </section>
    </main>
  </div>
</template>
