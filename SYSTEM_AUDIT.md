# Windows system audit and profile design

Audit snapshot: **04/09/2026 20:14-20:18 (UTC+7)**  
Scope: read-only inspection. No Windows, BIOS, Registry, service, task, driver, or application setting was changed during the audit. A temporary `powercfg /batteryreport` was generated in the Windows Temp directory only to read battery capacity.

## 1. Cấu hình máy

### Windows và firmware

| Hạng mục | Kết quả |
| --- | --- |
| Windows | Windows 11 Home Single Language 64-bit, 25H2, build 26200.9168 |
| Cài Windows | 11/02/2025 |
| Boot gần nhất tại thời điểm audit | 04/09/2026 11:24 |
| Laptop | Lenovo LOQ 15IAX9, machine type 83GS |
| Mainboard | LENOVO LNVNB161216, SDK0T76502 WIN |
| BIOS | NECN50WW, 16/01/2026 |

Lưu ý: giá trị `ProductName` cũ trong Registry vẫn ghi “Windows 10”, nhưng nguồn hệ thống CIM và build xác nhận đây là Windows 11. Đây là hành vi tương thích thường gặp, không phải lỗi cần sửa Registry.

### CPU, RAM và GPU

| Hạng mục | Kết quả |
| --- | --- |
| CPU | Intel Core i5-12450HX thế hệ 12, 8 core / 12 thread |
| Power management hiện tại | Balanced; min CPU 5%, max 100%; boost `Aggressive`; EPP gốc 45 AC / 50 DC; cooling Active AC / Passive DC |
| iGPU | Intel UHD Graphics, driver 32.0.101.7026 (19/08/2025) |
| dGPU | NVIDIA GeForce RTX 2050 4 GB, Windows driver 32.0.15.8186 / package NVIDIA 581.86 |
| Màn hình | 1920×1080; đường hiển thị Intel báo 144 Hz. Giá trị 60 Hz trên adapter NVIDIA không nhất thiết là refresh rate của panel nội bộ |
| RAM | 16 GB DDR5, 2×8 GB: SK Hynix DDR5-5600 + Kingston DDR5-4800; cả hai đang chạy 4800 MT/s |

Hai thanh RAM khác hãng/tốc độ nhưng có cùng dung lượng và đang chạy cùng 4800 MT/s. Audit không phát hiện lỗi, nhưng cấu hình trộn kit có thể có biên ổn định thấp hơn kit đồng bộ; chỉ coi đây là điểm cần theo dõi nếu có crash/WHEA, không phải lý do tự động thay RAM.

### Lưu trữ

Ổ vật lý duy nhất là **SOLIDIGM SSDPFINW512GZL 512 GB NVMe**, firmware C02C, trạng thái `Healthy/OK`.

| Volume | Dung lượng | Trống | Tỷ lệ trống |
| --- | ---: | ---: | ---: |
| C: Windows-SSD | 248.3 GiB | 41.9 GiB | 16.9% |
| D: Game | 210.0 GiB | 46.7 GiB | 22.3% |
| E: TaiLieu_DoAn | 16.5 GiB | 4.2 GiB | 25.2% |

C: chưa ở mức nguy cấp nhưng đã dưới ngưỡng thoải mái 20%. Nên giữ tối thiểu khoảng 35-50 GiB trống để Windows Update, shader cache, build cache và pagefile có biên làm việc.

### Pin

| Hạng mục | Kết quả |
| --- | ---: |
| Model | L23M4PK4, SMP, Li-Polymer |
| Design capacity | 60,000 mWh |
| Full charge capacity | 53,310 mWh |
| Battery health ước tính | **88.85%** |
| Cycle count | 335 |
| Trạng thái lúc audit | 97%, cắm AC, không sạc/xả |
| Runtime estimate trong battery report | khoảng 2 giờ 02 phút ở full-charge capacity, phụ thuộc workload lịch sử |

Pin đã hao mòn khoảng 11.15%, vẫn ở mức sử dụng được. Trạng thái “cắm AC nhưng không sạc” có thể do ngưỡng sạc/OEM hoặc điều kiện pin lúc đó; audit không đọc được chính xác Conservation Mode nên không suy đoán là đang bật.

### Power plan và các tính năng Windows

| Hạng mục | Hiện tại |
| --- | --- |
| Base power plan | Balanced (`381b4222-f694-41f0-9685-ff5bb260df2e`) |
| Power-mode overlay AC/DC | **Max Performance Overlay** cho cả AC và DC |
| Power plans khác | Driver Booster Power Plan, bitsum, Ultimate Performance |
| Display timeout | 3 phút AC / 3 phút DC |
| Sleep timeout | 10 phút AC / 10 phút DC |
| Brightness trong plan | 100% AC / 100% DC |
| PCIe Link State | Maximum power savings AC/DC |
| Wake timers | Enabled AC/DC |
| Hibernate | Tắt; hệ thống hỗ trợ S3 nhưng không có Hibernate/Hybrid Sleep/Fast Startup khả dụng |
| Fast Startup Registry | Cờ bằng 1 nhưng **không có hiệu lực** vì Hibernate đang tắt |
| Game Mode | Bật (`AutoGameModeEnabled=1`, `AllowAutoGameMode=1`) |
| Game DVR capture | Tắt (`GameDVR_Enabled=0`) |
| HAGS | Bật (`HwSchMode=2`) |
| Variable Refresh Rate | Bật (`VRROptimizeEnable=1`) |
| Optimizations for windowed games | Bật (`SwapEffectUpgradeEnable=1`) |
| Background apps | Tắt toàn cục cho tài khoản hiện tại (`GlobalUserDisabled=1`) |
| Windows Search | Running, Automatic |
| Pagefile | `D:\pagefile.sys`, system-managed trên D:, 15,360 MB cấp phát; dùng 3,371 MB, peak 5,810 MB |

Pagefile nằm trên một phân vùng khác nhưng vẫn cùng SSD vật lý. Không có bằng chứng rằng chuyển pagefile sang C: sẽ tăng FPS; giữ system-managed là lựa chọn an toàn.

### Startup applications

Đang được phép khởi động cùng Windows:

- EVKey64.
- Google Chrome auto-launch nền.
- Windows Security notification icon.
- Realtek Audio Universal Service.
- Riot Vanguard tray/service.

Các mục đã bị người dùng disable trong StartupApproved gồm Edge auto-launch, Riot Client, Discord, IDM, Lenovo Vantage Toolbar, Zalo, Steam, TeraBox, Blitz và một số mục khác. Không cần disable lặp lại bằng Registry.

### Process nền và workload tại thời điểm đo

Máy đang chạy League of Legends trong lúc lấy mẫu, nên đây là snapshot tải thực tế khi gaming chứ không phải idle baseline.

| Nhóm process | Working set tổng gần đúng | Ghi chú |
| --- | ---: | --- |
| Brave (18 process) | 3.44 GB | Tải nền lớn nhất ngoài game |
| ChatGPT/Codex UI (8 process) | 1.72 GB | Đang dùng cho audit |
| League of Legends | 1.10 GB | Game process, khoảng 14.2% tổng CPU trong mẫu 3 giây |
| LeagueClient | 1.01 GB | Khoảng 5.8% tổng CPU trong mẫu |
| LeagueClientUxRender (6 process) | 0.88 GB | Client/UI, không phải renderer chính của game |
| Blitz (5 process) | 0.73 GB | Overlay/companion đáng chú ý |
| Node (12 process) | 0.65 GB | Dev workload nền |
| Riot Client (6 process) | 0.43 GB | Có thể đóng sau khi game đã vào nếu client cho phép |
| Microsoft Defender | 0.36 GB | Bảo vệ thời gian thực, giữ nguyên |

Windows chỉ còn khoảng **1.0 GB RAM vật lý trống** trong snapshot và pagefile đang dùng hơn 3.3 GB. Đây là yếu tố có khả năng gây stutter rõ hơn nhiều so với các “registry gaming tweak”.

### Services, scheduled tasks và phần mềm OEM

Các service bên thứ ba đáng chú ý đang tự chạy:

- Apache 2.4, XAMPP MySQL.
- SQL Server Express, SQL Server telemetry và SQL Writer.
- Cloudflare WARP.
- Garena Platform.
- Microsoft PC Manager.
- Remio Host và UltraViewer remote-access services.
- Lenovo Vantage Service, Lenovo Fn/Utility, Lenovo UDC.
- Intel Dynamic Tuning / Innovation Platform Framework.
- NVIDIA Display Container.
- The Hidden Gaming Lair Bridge Host.
- Windhawk và WSL Service.

Phần mềm quản lý/tuning phát hiện được: Lenovo Legion Toolkit 2.26.1, Lenovo Vantage Service 5.1.2608.14, MSI Afterburner, RivaTuner Statistics Server, The Hidden Gaming Lair, Microsoft PC Manager, Windhawk. Thermal/fan mode Lenovo hiện tại không đọc được an toàn qua WMI; các script không cố gọi API OEM không tài liệu.

Scheduled tasks đáng chú ý:

- MiniTool Partition Wizard update checker chạy lúc logon.
- Garena silent launcher chạy lúc logon.
- Remio Host chạy lúc logon.
- Opera update tasks, Zoom update mỗi giờ, Google Platform Experience Helper.
- Office update/maintenance tasks.
- Defender idle maintenance/scans và Windows Update tasks: bình thường, giữ nguyên.
- `Activation-Renewal` chạy hàng tuần từ `%ProgramFiles%\Activation-Renewal\Activation_task.cmd` và `CreateExplorerShellUnelevatedTask` dùng `/NoUACCheck`: không phải tối ưu hiệu năng; nên kiểm tra nguồn gốc vì có ý nghĩa bảo mật/độ tin cậy.

### Overlay và graphics preference

- Đang chạy: Blitz/companion processes và Modskinlol cùng League/Riot Client.
- Không thấy process Discord, Steam, Xbox Game Bar, NVIDIA Share/nvsphelper, MSI Afterburner hoặc RTSS đang hoạt động trong snapshot.
- Windows đã gán `GpuPreference=2` (High performance) cho một số game/app, nhưng League chỉ có `AppStatus=4096` và chưa có `GpuPreference` rõ ràng.
- Có ba virtual display adapter: hai DeskIn và một SudoMaker. Chúng phục vụ remote/virtual display nhưng có thể làm phức tạp display path, capture và anti-cheat. Không xóa driver trong profile.

### Windows Update và Defender

- Không phát hiện Group Policy tắt Windows Update. `UsoSvc` và BITS đang chạy; `wuauserv` Manual/Stopped tại thời điểm audit là trạng thái có thể bình thường khi không cập nhật; Delivery Optimization Manual/Stopped.
- Active Hours lưu 09:00-03:00; các mốc pause update cũ đã hết hạn.
- Defender Antivirus, realtime protection, behavior monitor, IOAV, network inspection và Tamper Protection đều bật; signatures không lỗi thời.
- Defender scheduled scan chạy khi idle, `ScanAvgCPULoadFactor=50`. Không có lý do tắt Defender cho gaming.

## 2. Các vấn đề phát hiện

1. **Max Performance Overlay đang áp dụng cả khi dùng pin.** Đây là cấu hình xung đột trực tiếp với mục tiêu pin, dù base plan mang tên Balanced.
2. **Áp lực RAM cao trong gaming.** 16 GB gần đầy; Brave, ChatGPT, Blitz, Node và nhiều client phụ chiếm vài GB bên cạnh game. Pagefile đang được dùng đáng kể.
3. **Nhiều database/dev/remote service chạy Automatic.** Apache, MySQL, SQL Server, SQL telemetry, SQL Writer, Remio Host, UltraViewer, WSL và các service phụ làm tăng wakeups, RAM và I/O nền.
4. **Nhiều lớp overlay/hook/virtual display.** Blitz, Modskinlol, Windhawk, DeskIn/SudoMaker adapters và bộ MSI Afterburner/RTSS đã cài có thể ảnh hưởng frame pacing, capture hoặc anti-cheat khi cùng hoạt động.
5. **Dấu vết của các công cụ “tuning” cũ.** Driver Booster plan, bitsum plan, Ultimate Performance, SysMain bị Disabled và toàn bộ Xbox services bị Disabled. Không nên tiếp tục xếp chồng tweak khi chưa đo A/B.
6. **Hibernate bị tắt.** Fast Startup vì vậy không khả dụng; ở mức pin critical, hành động Sleep không bảo vệ tốt như Hibernate nếu máy tiếp tục hao pin lâu.
7. **Scheduled task cần kiểm tra nguồn gốc.** `Activation-Renewal` và task Explorer `/NoUACCheck` là vấn đề security/integrity tiềm ẩn, không phải bài toán FPS.
8. **Dung lượng trống C: chỉ 16.9%.** Chưa khẩn cấp nhưng nên tránh để thấp hơn nữa.

## 3. Thành phần đang ảnh hưởng pin

- Max Performance Overlay trên DC là tác động trực tiếp và rõ nhất.
- Độ sáng plan 100% và panel 144 Hz làm tăng điện năng màn hình.
- CPU boost đang `Aggressive` cả AC/DC; max CPU 100% trên pin.
- Chrome auto-launch cùng các database, remote access, WSL, PC Manager, Garena, Vantage add-ins và update helpers tạo tải nền/wakeups.
- DeskIn/SudoMaker virtual display stack và remote host services có thể giữ GPU/display subsystem hoạt động.
- Cloudflare WARP làm tăng một phần xử lý mạng; chỉ tắt khi đã xác nhận không cần VPN/DNS/security của WARP.
- Battery health 88.85% nghĩa là cùng workload, thời lượng thực tế thấp hơn khoảng 11% so với pin mới.

## 4. Thành phần có thể ảnh hưởng gaming

- Thiếu headroom RAM và paging là ứng viên gây stutter số một trong snapshot.
- Blitz dùng khoảng 0.73 GB, cùng Modskinlol và nhiều Riot/League client process; overlay/hook có thể tăng frametime variance.
- Brave 3.44 GB, ChatGPT 1.72 GB, Node 0.65 GB làm giảm cache/headroom cho game.
- Apache/MySQL/SQL Server/remote services có thể tạo CPU wakeup, I/O hoặc network activity không đúng lúc.
- Cloudflare WARP có thể thay đổi route/latency; ảnh hưởng tốt hay xấu phụ thuộc tuyến mạng, phải đo ping/jitter thực tế.
- HAGS và VRR đang bật. Đây không phải lúc thích hợp để đổi mù; cần benchmark từng game nếu gặp stutter/compatibility issue.
- Virtual display drivers, Windhawk, RTSS/Afterburner và các hook khác có thể xung đột với anti-cheat/capture. Chỉ chạy thành phần thật sự cần.
- Lenovo thermal mode quyết định power/fan envelope nhiều hơn một số power-plan tweak. Chưa đọc được mode hiện tại nên cần xác nhận thủ công trong Lenovo Legion Toolkit/Vantage.

## 5. Những tối ưu an toàn

- Tạo hai power plan riêng bằng cách duplicate Balanced; không sửa trực tiếp plan gốc.
- Battery: dùng Better Battery-life Overlay, giới hạn CPU hợp lý, ưu tiên EPP tiết kiệm, cooling Passive, giảm brightness/timeout và tắt wake timer trên DC.
- Gaming: dùng Max Performance Overlay, CPU max 100%, cooling Active, PCIe ASPM Off và giữ CPU min 5% để tránh nhiệt/năng lượng vô ích khi idle.
- Giữ Game Mode, HAGS, VRR, Defender, Windows Update, Search và pagefile như hiện tại.
- Gán League vào High-performance GPU chỉ khi executable và RTX 2050 được phát hiện tại runtime.
- Trước gaming, đóng thủ công browser tab nặng, Blitz/overlay không cần, dev server và client launcher không cần. Đóng thủ công giúp tránh mất dữ liệu.
- Cho phép dừng tạm thời một whitelist service dev/remote bằng switch rõ ràng; không đổi StartupType, và restore chỉ khởi động lại service mà script thực sự đã dừng.
- Dùng Lenovo Quiet mode cho Battery và Performance mode cho Gaming bằng Fn+Q/Legion Toolkit sau khi người dùng kiểm tra giao diện OEM; không tự động hóa API OEM chưa được xác minh.
- Chuyển 60 Hz khi Battery và 144 Hz khi Gaming bằng Windows/Lenovo UI sau khi xác nhận panel; script không hard-code refresh rate.

## 6. Những tối ưu có rủi ro

- Dừng database/dev/remote services có thể làm mất kết nối local dev hoặc remote session. Vì vậy chỉ có dưới switch `-IncludeOptionalServiceStops`.
- Bật Hibernate sẽ tạo `hiberfil.sys`, dùng thêm nhiều GB và thay đổi boot/sleep behavior. Nên cân nhắc riêng, không nằm trong script.
- Đổi HAGS/VRR có thể cải thiện hoặc làm xấu frametime tùy game/driver và cần reboot; không tự động đổi.
- Tắt Cloudflare WARP có thể đổi route và làm mất chính sách DNS/VPN; chỉ A/B test thủ công.
- Ép dGPU-only/MUX mode có thể tăng FPS nhưng tăng điện, nhiệt và cần logout/reboot; chỉ đổi trong Lenovo UI khi cắm sạc.
- Disabling core parking, HPET, dynamic tick, timer resolution hoặc network throttling bằng tweak trên Internet có thể tăng điện/nhiệt hoặc gây regression; không áp dụng nếu chưa benchmark ETW/PresentMon/LatencyMon có đối chứng.
- Gỡ virtual display/filter/overlay drivers có thể phá remote access hoặc networking; không nằm trong profile.
- Điều tra/xóa scheduled task kích hoạt hoặc `/NoUACCheck` là thao tác security riêng, cần xác minh license/nguồn gốc trước.

## 7. Những thứ KHÔNG nên thay đổi

- Không disable Windows Defender, Tamper Protection, Windows Update, BITS, security services hoặc firewall.
- Không disable hàng loạt Windows services; đặc biệt giữ Lenovo/Intel thermal and power framework, Realtek audio và NVIDIA Display Container.
- Không xóa Registry, driver, power plan cũ hoặc phần mềm trong script profile.
- Không sửa BIOS, firmware, voltage, overclock, undervolt, CPU/GPU power limit hoặc custom fan table.
- Không đặt CPU minimum 100% cho gaming; cách này tăng nhiệt idle và có thể làm giảm boost headroom dài hạn.
- Không tắt pagefile. Snapshot đã dùng hơn 3 GB pagefile; với RAM 16 GB, tắt pagefile tăng nguy cơ out-of-memory/crash.
- Không tự động enable/disable SysMain hoặc Xbox services thêm lần nữa. Trạng thái hiện tại đã bị tweak; chỉ thay sau benchmark hoặc khi một game/tính năng cụ thể yêu cầu.
- Không dùng Driver Booster/tweak pack để cập nhật driver. Ưu tiên Lenovo Vantage/Support cho chipset/OEM và NVIDIA/Intel chính thức cho GPU sau khi kiểm tra compatibility.
- Không coi “Ultimate Performance” là mặc định tốt nhất cho laptop; FPS bền vững phụ thuộc thermal headroom, không chỉ xung tức thời.

## Thiết kế hai profile (chưa áp dụng)

| Setting | Current | Battery Mode | Gaming Mode | Lý do | Risk |
| --- | --- | --- | --- | --- | --- |
| Base plan | Balanced | Duplicate Balanced: `Fitness Battery Mode` | Duplicate Balanced: `Fitness Gaming Mode` | Cô lập thay đổi, không phá plan gốc | Thấp |
| Windows overlay AC/DC | Max Performance / Max Performance | Better Battery-life / Better Battery-life | Max Performance / Max Performance | Sửa xung đột lớn nhất trên pin | Thấp |
| CPU min state | 5% / 5% | 5% / 5% | 5% / 5% | Cho CPU hạ xung khi rảnh; gaming không cần min 100% | Thấp |
| CPU max state | 100% / 100% | 80% / 80% | 100% / 100% | Battery giảm nhiệt/quạt; gaming giữ headroom | Thấp-Trung bình |
| CPU boost mode | Aggressive / Aggressive | Efficient Enabled AC / Disabled DC | Aggressive / Aggressive | Battery giảm burst điện; gaming giữ boost hiện tại | Trung bình: build nặng chậm hơn ở Battery |
| CPU EPP | Gốc 45 AC / 50 DC, bị overlay Max can thiệp | 80 AC / 90 DC | 10 AC / 25 DC | Điều khiển thiên hướng efficiency/performance | Thấp-Trung bình |
| Cooling policy | Active AC / Passive DC | Passive / Passive | Active / Active | Battery ưu tiên ít quạt; Gaming ưu tiên xả nhiệt | Thấp |
| PCIe Link State | Maximum savings / Maximum savings | Maximum savings / Maximum savings | Off / Off | Gaming tránh power-state transition của dGPU | Thấp; Gaming tốn điện hơn |
| Brightness policy | 100% / 100% | 60% AC / 40% DC | 100% AC / 70% DC | Màn hình là tải pin lớn | Thấp |
| Display timeout | 3m / 3m | 5m AC / 3m DC | Never AC / 5m DC | Phù hợp workflow từng profile | Thấp |
| Sleep timeout | 10m / 10m | 15m AC / 10m DC | Never AC / 10m DC | Tránh sleep giữa game khi cắm sạc | Thấp |
| Wake timers | Enabled / Enabled | Important only AC / Disabled DC | Important only / Important only | Giảm tự thức máy trên pin | Thấp |
| Game Mode | On | Giữ On | On | Đã đúng; không có lợi rõ khi tắt ở Battery | Thấp |
| Game DVR capture | Off | Giữ Off | Giữ Off | Tránh capture nền, trạng thái hiện tại đã tốt | Thấp |
| HAGS | On | Giữ On | Giữ On | Tránh thay đổi cần reboot khi chưa A/B test | Thấp |
| VRR/windowed optimization | On / On | Giữ nguyên | Giữ nguyên | Có lợi tiềm năng và không có bằng chứng lỗi | Thấp |
| League GPU preference | Chưa chỉ định rõ | Giữ nguyên | High performance nếu đúng EXE tồn tại | Bảo đảm dùng RTX 2050 cho game đã phát hiện | Thấp |
| Panel refresh rate | 144 Hz được phát hiện | 60 Hz thủ công | 144 Hz thủ công | Tiết kiệm pin / độ mượt gaming | Thấp; không script vì API OEM chưa xác minh |
| Lenovo thermal mode | Không đọc được | Quiet thủ công | Performance thủ công khi cắm AC | OEM kiểm soát quạt và power envelope | Trung bình nếu dùng Performance khi tản nhiệt kém |
| Pagefile | System-managed trên D: | Giữ nguyên | Giữ nguyên | RAM 16 GB đang cần pagefile | Thấp |
| Search indexing | Running/Automatic | Giữ nguyên | Giữ nguyên | Không disable service hệ thống; Windows tự throttle | Thấp |
| Background apps | Global disabled | Giữ nguyên | Giữ nguyên | Đã tối ưu | Thấp |
| Defender | Fully enabled | Giữ nguyên | Giữ nguyên | Không hy sinh bảo mật | Thấp |
| Windows Update | Không policy disable | Giữ nguyên | Giữ nguyên | Không hy sinh bảo mật/ổn định | Thấp |
| Dev/remote services | Nhiều service Automatic/Running | Optional stop bằng switch | Optional stop bằng switch | Giảm RAM/I/O/wakeups khi chắc chắn không cần | Trung bình |
| Overlay/apps | Blitz và Modskinlol đang chạy | Đóng thủ công nếu không cần | Đóng thủ công overlay không cần | Tránh mất dữ liệu và anti-cheat conflict | Thấp-Trung bình |
| Hibernate/Fast Startup | Hibernate Off; Fast Startup unavailable | Giữ nguyên | Giữ nguyên | Tránh tạo hiberfile/thay boot behavior tự động | Thấp |

## Cách kiểm tra trước khi chạy script

1. Mở PowerShell thường và đọc script: `Get-Content .\battery_mode.ps1`, `Get-Content .\gaming_mode.ps1`, `Get-Content .\restore_default.ps1`.
2. Kiểm tra cú pháp mà không chạy script: `$tokens=$null; $errors=$null; [void][System.Management.Automation.Language.Parser]::ParseFile((Resolve-Path '.\battery_mode.ps1'),[ref]$tokens,[ref]$errors); $errors`. Lặp lại cho hai file còn lại; không có output nghĩa là không có parse error.
3. Ghi lại `powercfg /getactivescheme`, `powercfg /list`, `powercfg /a` và chụp Windows Settings > System > Power & battery.
4. Mở Lenovo Legion Toolkit/Vantage, ghi lại Thermal Mode, GPU mode/MUX, Conservation Mode và refresh rate. Script không thay các mục OEM này.
5. Đóng/commit công việc đang dùng Apache, MySQL, SQL Server hoặc remote access. Không dùng `-IncludeOptionalServiceStops` nếu còn cần chúng.
6. Với Gaming Mode, xác nhận file `D:\Game\Riot Games\League of Legends\Game\League of Legends.exe` tồn tại. Nếu không, script sẽ bỏ qua GPU preference thay vì hard-code sai.
7. Sau khi tự quyết định chạy, mở PowerShell **Run as Administrator** và chạy không có switch trước. Chỉ thêm `-IncludeOptionalServiceStops` khi hiểu danh sách service trong script.
8. Sau mỗi lần áp dụng, kiểm tra lại `powercfg /getactivescheme`, Windows Power mode, Task Manager > Performance, và Lenovo thermal mode. Benchmark cùng game/settings/cảnh trong ít nhất 3 lượt; ghi FPS 1% low, frametime, nhiệt độ, công suất và fan noise.
9. Nếu có lỗi, chạy `restore_default.ps1` bằng Administrator. Script dùng baseline JSON, `.reg` export và power-plan export đã lưu ở `windows_profile_backup`.
