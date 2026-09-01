<template>
  <div class="bo-cuc-cong-khai">
    <main class="bo-cuc-cong-khai__main">
      <section
        class="bo-cuc-cong-khai__thuong-hieu"
        aria-label="Giới thiệu Smart Fitness Platform"
      >
        <div class="thuong-hieu__dau-trang">
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
          <span class="thuong-hieu__ten">SMART FITNESS</span>
        </div>

        <div class="thuong-hieu__noi-dung">
          <p class="thuong-hieu__nhan">
            WORKSPACE / 01
          </p>
          <h1>Vận hành phòng tập<br><em>thông minh hơn.</em></h1>
          <p class="thuong-hieu__mo-ta">
            Một cổng làm việc tập trung cho quản trị viên, huấn luyện viên và lễ tân.
          </p>
        </div>

        <div class="thuong-hieu__chan-trang">
          <span
            class="thuong-hieu__cham"
            aria-hidden="true"
          />
          <span>Hệ thống đang sẵn sàng</span>
        </div>
      </section>

      <section
        class="bo-cuc-cong-khai__the"
        aria-label="Khu vực công khai"
      >
        <slot />
      </section>
    </main>
  </div>
</template>
